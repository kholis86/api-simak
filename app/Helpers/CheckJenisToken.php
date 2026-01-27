<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CheckJenisToken
{
    /**
     * Ambil nama token langsung dari Request
     *
     * @param Request $request
     * @return string|null  Nama token jika valid, null jika tidak
     */
    public static function getName(Request $request): ?string
    {
        $plainToken = $request->bearerToken(); // ambil token dari header Authorization

        if (!$plainToken) {
            return null;
        }

        // pisah id dan token
        [$id, $token] = explode('|', $plainToken, 2);

        $accessToken = PersonalAccessToken::find($id);

        if (!$accessToken) {
            return null;
        }

        // cek token hash
        if (!hash_equals($accessToken->token, hash('sha256', $token))) {
            return null;
        }

        return $accessToken->name; // langsung return nama token
    }
}
