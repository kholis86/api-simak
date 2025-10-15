<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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
