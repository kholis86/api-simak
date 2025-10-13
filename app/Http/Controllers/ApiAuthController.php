<?php

namespace App\Http\Controllers;

use App\Models\ApiUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/token/login",
     *     tags={"Auth"},
     *     summary="Login to get API token",
     *     description="Authenticate user with username & password, returns Bearer token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="api-simak"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|abcdef123456..."),
     *             @OA\Property(property="expired_at", type="string", example="2025-09-29 14:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = ApiUser::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'message' => 'Invalid Token credentials',
                'data'    => null
            ], 401);
        }

        $tokenResult = $user->createToken('api-token');
        $token = $tokenResult->plainTextToken;

        // Set expired_at 24 jam
        $expiredAt = now()->addHours(24);
        // $expiredAt = now()->addSecond();

        // Update di tabel tokens (kalau pakai Sanctum, simpan manual di kolom expires_at)
        $user->tokens()
            ->where('id', $tokenResult->accessToken->id ?? null) // sesuaikan kalau butuh id
            ->update(['expires_at' => $expiredAt]);

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Login successful',
            'data'    => [
                'token'      => $token,
                'expired_at' => $expiredAt->toDateTimeString(),
            ]
        ], 200);
    }


    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
