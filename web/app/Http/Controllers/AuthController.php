<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        // akun demo yang masih ada di database, untuk tombol "Masuk sebagai ..."
        $ada = User::whereIn('email', array_column(config('akun_demo'), 'email'))->pluck('email')->all();
        $demo = array_values(array_filter(config('akun_demo'), fn ($a) => in_array($a['email'], $ada)));

        return view('auth.login', ['demo' => $demo]);
    }

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Auth::attempt mencocokkan password dengan hash bcrypt di tabel users
        if (! Auth::attempt($kredensial, $request->boolean('ingat'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
