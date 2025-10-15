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
     *     tags={"API Auth"},
     *     summary="Login dan dapatkan token",
     *     description="Autentikasi user API, mengembalikan plainText token dan expired_at",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="user1"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="plain_text_token_here"),
     *                 @OA\Property(property="expired_at", type="string", format="date-time", example="2025-10-16 12:34:56")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid Token credentials"),
     *             @OA\Property(property="data", type="null", nullable=true)
     *         )
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
