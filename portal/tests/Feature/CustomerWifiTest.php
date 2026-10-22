<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerWifiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Subscriber}
     */
    private function customer(): array
    {
        $plan = Plan::query()->create([
            'name' => 'P50',
            'speed_mbps' => 50,
            'price' => 70,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        $router = Router::query()->create([
            'name' => 'R1',
            'host' => '10.0.0.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'subscriber',
            'is_active' => true,
            'phone' => '0599000111',
        ]);
        $subscriber = Subscriber::query()->create([
            'user_id' => $user->id,
            'full_name' => 'عميل تجريبي',
            'phone' => '0599000111',
            'pppoe_username' => 'u'.uniqid(),
            'pppoe_password' => 'x',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'monthly_price' => 70,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        return [$user, $subscriber];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function acsDevice(string $serial = 'ABC123'): array
    {
        return [[
            '_id' => '00E0-HG-'.$serial,
            '_lastInform' => now()->toIso8601String(),
            '_deviceId' => ['_SerialNumber' => $serial],
            'InternetGatewayDevice' => [
                'LANDevice' => [
                    '1' => [
                        'WLANConfiguration' => [
                            '1' => [
                                'SSID' => ['_value' => 'MADD_Home'],
                                'KeyPassphrase' => ['_value' => 'secret12'],
                            ],
                        ],
                        'Hosts' => [
                            'Host' => [
                                '1' => [
                                    'HostName' => ['_value' => 'iPhone 15 Pro'],
                                    'MACAddress' => ['_value' => 'AA:BB:CC:DD:EE:FF'],
                                    'IPAddress' => ['_value' => '192.168.1.10'],
                                    'Active' => ['_value' => true],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]];
    }

    public function test_wifi_status_when_acs_unconfigured(): void
    {
        config(['services.genieacs.nbi_url' => '']);
        [$user] = $this->customer();

        Sanctum::actingAs($user);

        $this->getJson('/api/customer/wifi')
            ->assertOk()
            ->assertJsonPath('linked', false)
            ->assertJsonPath('code', 'acs_unconfigured');
    }

    public function test_claim_and_read_ssid_and_devices(): void
    {
        config(['services.genieacs.nbi_url' => 'http://acs.test:7557']);
        Http::fake([
            'acs.test:7557/*' => Http::response($this->acsDevice(), 200),
        ]);

        [$user] = $this->customer();
        Sanctum::actingAs($user);

        $this->postJson('/api/customer/wifi/claim', ['serial' => 'abc123'])
            ->assertOk()
            ->assertJsonPath('linked', true)
            ->assertJsonPath('ssid', 'MADD_Home')
            ->assertJsonPath('devices.0.name', 'iPhone 15 Pro');

        $this->assertDatabaseHas('subscribers', [
            'user_id' => $user->id,
            'acs_serial' => 'ABC123',
            'acs_device_id' => '00E0-HG-ABC123',
        ]);
    }

    public function test_update_ssid_sends_set_parameter_values(): void
    {
        config(['services.genieacs.nbi_url' => 'http://acs.test:7557']);
        $device = $this->acsDevice();
        Http::fake(function ($request) use ($device) {
            if (str_contains($request->url(), '/tasks')) {
                return Http::response(['_id' => 'task'], 200);
            }

            return Http::response($device, 200);
        });

        [$user, $subscriber] = $this->customer();
        $subscriber->update([
            'acs_serial' => 'ABC123',
            'acs_device_id' => '00E0-HG-ABC123',
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/customer/wifi', ['ssid' => 'MADD_New'])
            ->assertOk();

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/tasks')
                && ($data['name'] ?? '') === 'setParameterValues'
                && ($data['parameterValues'][0][1] ?? '') === 'MADD_New';
        });
    }
}
