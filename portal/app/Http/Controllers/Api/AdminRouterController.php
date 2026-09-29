<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminRouterController extends Controller
{
    public function __construct(private readonly MikrotikService $mikrotikService)
    {
    }

    public function index()
    {
        return Router::query()->orderBy('name')->get();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $router = Router::create($data);

        return response()->json($router, 201);
    }

    public function show(Router $router): JsonResponse
    {
        return response()->json($router);
    }

    public function update(Request $request, Router $router): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'host' => ['sometimes', 'string', 'max:255'],
            'api_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $request->filled('password')) {
            unset($data['password']);
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $router->update($data);

        return response()->json($router->fresh());
    }

    public function destroy(Router $router): Response
    {
        $router->delete();

        return response()->noContent();
    }

    public function test(Router $router): JsonResponse
    {
        try {
            $info = $this->mikrotikService->fetchRouterInfo($router);

            return response()->json($info);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function sync(Router $router): JsonResponse
    {
        try {
            $stats = $this->mikrotikService->syncRouter($router);

            return response()->json($stats);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function syncSecrets(Router $router): JsonResponse
    {
        try {
            $result = $this->mikrotikService->syncPppSecretsToDatabase($router);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function importSubscribersFromPpp(Router $router): JsonResponse
    {
        try {
            $result = $this->mikrotikService->importSubscribersFromPppSecrets($router);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pushSubscriberCommentsToMikrotik(Router $router): JsonResponse
    {
        try {
            $result = $this->mikrotikService->syncSubscriberCommentsToMikrotik($router);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pppProfiles(Router $router): JsonResponse
    {
        try {
            $profiles = $this->mikrotikService->fetchPppProfiles($router);

            return response()->json(['profiles' => $profiles]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function createPlansFromProfiles(Router $router): JsonResponse
    {
        try {
            $result = $this->mikrotikService->createPlansFromMikrotikProfiles($router);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function syncSubscriberPlansFromMikrotik(Router $router): JsonResponse
    {
        try {
            $result = $this->mikrotikService->syncSubscribersPlansWithMikrotik($router);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
