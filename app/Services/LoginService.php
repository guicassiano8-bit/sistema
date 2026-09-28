<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginService
{
    public function attempt(array $credentials, bool $remember = false): bool
    {
        if (Auth::attempt($credentials, $remember)) {
            session()->regenerate();

            return true;
        }

        return false;
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
