<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'nik'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nik.required'      => 'NIK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $kunci = Str::lower($kredensial['nik']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);
            return back()
                ->withErrors(['nik' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik."])
                ->onlyInput('nik');
        }

        if (Auth::attempt($kredensial, $request->boolean('remember'))) {
            RateLimiter::clear($kunci);
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        RateLimiter::hit($kunci, 60);

        return back()
            ->withErrors(['nik' => 'NIK atau password salah.'])
            ->onlyInput('nik');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah logout.');
    }
}