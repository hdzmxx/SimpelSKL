<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.login');
    }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|string', 'password' => 'required|string']);
        if (Auth::attempt($c, $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->route('dashboard');
        }
        return back()->withErrors(['email' => 'Email/username atau password salah.'])->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
