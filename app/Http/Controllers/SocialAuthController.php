<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{

    public function redirectToProvider()
    {
        // Menggunakan stateless() sangat penting untuk otentikasi API/SPA
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleProviderCallback()
    {
        try {
            // Menggunakan stateless()
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            // Tangani error, alihkan kembali ke frontend dengan pesan error
            return redirect(env('FRONTEND_URL') . '/login?error=google_auth_failed');
        }

        // Cari user berdasarkan email
        // $user = User::where('email', $googleUser->getEmail())->first();
        $user = AcdStudent::where('Email_Corporate', $googleUser->getEmail())->first();

        if ($user) {
            // User sudah ada, lakukan login
        } else {
            return redirect(env('FRONTEND_URL') . '/login?error=user_not_found');
        }

        // --- Bagian Penting Sanctum ---
        // Hapus token lama jika ada, lalu buat token baru untuk API
        $tokenResult = $user->createToken('mahasiswa-token');
        $token = $tokenResult->plainTextToken;
        // Set expired_at 24 jam
        $expiredAt = now()->addHours(24);
        // $expiredAt = now()->addSecond();

        // Update di tabel tokens (kalau pakai Sanctum, simpan manual di kolom expires_at)
        $user->tokens()
            ->where('id', $tokenResult->accessToken->id ?? null) // sesuaikan kalau butuh id
            ->update(['expires_at' => $expiredAt]);

        // Redirect kembali ke Nuxt.js dengan token di URL
        return redirect(env('FRONTEND_URL') . '/auth/callback?token=' . $token . '&expires_at=' . $expiredAt);
    }
}
