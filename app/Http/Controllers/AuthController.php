<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/login",
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


    use ApiResponseTrait;

    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|min:3|max:50',
                'password' => 'required|string|min:3|max:100',
            ]);

            $mahasiswa = AcdStudent::where('Nim', $request->username)->first();

            $validPassword = false;
            if ($mahasiswa) {
                $validPassword = hash_equals($mahasiswa->Student_Password, md5($request->password));
            }

            if (! $mahasiswa || ! $validPassword) {
                return $this->errorResponse('Invalid credentials', 'Username or password incorrect', 401);
            }
            $data = [
                'Student_Id'      => $mahasiswa->Student_Id,
                'Nim'             => $mahasiswa->Nim,
                'Full_Name'       => $mahasiswa->Full_Name,
                'Register_Number' => $mahasiswa->Register_Number,
            ];

            return $this->successResponse('Login successful', $data, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            // 5️⃣ Tangkap error tak terduga (misal DB down, field salah, dll)
            return $this->errorResponse(
                'Internal server error',
                $e->getMessage(),
                500
            );
        }
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
