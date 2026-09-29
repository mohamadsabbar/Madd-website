<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole();
    }

    public function showLogoutConfirm()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.logout-confirm');
    }

    public function logout(Request $request)
    {
        $wasSubscriber = Auth::user()?->role === 'subscriber';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasSubscriber ? 'web.customer.login' : 'login');
    }

    private function redirectByRole()
    {
        $user = Auth::user();

        return match ($user->role) {
            'admin' => redirect()->route('web.admin.dashboard'),
            'subscriber' => redirect()->route('web.customer.dashboard'),
            default => redirect()->route('web.agent.dashboard'),
        };
    }
}
