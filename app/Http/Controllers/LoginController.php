<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LoginService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(private LoginService $loginService) {}

    public function index()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $authenticated = $this->loginService->attempt($request->validated(), $request->boolean('remember'));

        if (! $authenticated) {
            return back()->withErrors(['email' => 'Credenciais Inválidas'])->onlyInput('email');
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        $this->loginService->logout($request);

        return redirect()->route('login.index');
    }
}
