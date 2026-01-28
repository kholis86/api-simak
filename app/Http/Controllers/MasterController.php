<?php

namespace App\Http\Controllers;

use App\Helpers\CheckJenisToken;
use App\Http\Controllers\Controller;
use App\Models\AcdStudentKrs;
use App\Models\MstrEventSched;
use App\Models\MstrTermYear;
use Illuminate\Http\Request;
use App\Models\MstrDepartment;
use App\Models\MstrClassProgram;
use App\Models\MstrReligion;
use App\Models\MstrMaritalStatus;
use App\Traits\ApiResponseTrait;

class MasterController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/master/departments",
     *     tags={"Master"},
     *     summary="Get department list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Department list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_department list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Department_Id", type="integer", example=1),
     *                     @OA\Property(property="Department_Name", type="string", example="Hukum")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */
    public function departments(Request $request)
    {
        $query = MstrDepartment::select('Department_Id', 'Department_Name');

        // filter by Department_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Department_Name', 'like', '%' . $request->search . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'mstr_department list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/program-classes",
     *     tags={"Master"},
     *     summary="Get program class list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Program class list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_program_class list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Program_Class_Id", type="integer", example=1),
     *                     @OA\Property(property="Program_Class_Name", type="string", example="Reguler")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function classPrograms(Request $request)
    {
        $query = MstrClassProgram::select('Class_Prog_Id', 'Class_Prog_Name');

        // filter by Class_Prog_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Class_Prog_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'mstr_class_program list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/religions",
     *     tags={"Master"},
     *     summary="Get religion list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Religion list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_religion list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Religion_Id", type="integer", example=1),
     *                     @OA\Property(property="Religion_Name", type="string", example="Islam")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function religions(Request $request)
    {
        $query = MstrReligion::select('Religion_Id', 'Religion_Name');

        // filter by Religion_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Religion_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'religion list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/marital-statuses",
     *     tags={"Master"},
     *     summary="Get marital status list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Marital status list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_marital_status list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Marital_Status_Id", type="integer", example=1),
     *                     @OA\Property(property="Marital_Status_Name", type="string", example="Belum Menikah")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function maritalStatuses(Request $request)
    {
        $query = MstrMaritalStatus::select('Marital_Status_Id', 'Marital_Status_Name');

        // filter by Marital_Status_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Marital_Status_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'marital_status list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    public function termYear(Request $request)
    {
        try {
            // Ambil jenis token dan data user
            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            // 1. Tentukan Department ID
            $departmentId = null;

            // Logika Prioritas 1: Jika user adalah Mahasiswa, ambil Department_Id dari data user
            if ($tokenName == 'mahasiswa-token' && $dataBearer) {
                $departmentId = $dataBearer->Department_Id;
            }

            // Logika Prioritas 2: Jika BUKAN mahasiswa-token, cek apakah Department_Id dikirim via Request
            elseif ($request->has('department_id')) {
                $departmentId = $request->department_id;
            }

            // 2. Query utama untuk Term Year (Diubah untuk filter Mahasiswa)
            $query = MstrTermYear::select('*');

            // --- Perubahan Kunci: Filtering untuk Mahasiswa ---
            if ($tokenName == 'mahasiswa-token' && $dataBearer) {
                // Dapatkan daftar Term_Year_Id yang pernah diikuti oleh mahasiswa
                $followedTermIds = AcdStudentKrs::where('Student_Id', $dataBearer->Student_Id)
                    ->distinct('Term_Year_Id') // Hanya ambil ID yang unik
                    ->pluck('Term_Year_Id'); // Ambil sebagai array 

                // Filter MstrTermYear berdasarkan Term_Year_Id yang pernah diikuti
                $query->whereIn('Term_Year_Id', $followedTermIds);
            }
            // --------------------------------------------------

            $terms = $query
                ->orderByRaw('CASE WHEN NOW() BETWEEN Start_Date AND End_Date THEN 1 ELSE 0 END DESC')
                ->orderBy('Term_Year_Id', 'DESC')
                ->get();

            // 3. Terapkan default NULL untuk Start_Krs_Date dan End_Krs_Date pada SEMUA hasil
            $terms = $terms->map(function ($term) {
                $term->Start_Krs_Date = null;
                $term->End_Krs_Date = null;
                return $term;
            });

            // 4. Terapkan Conditional Mapping (Override) HANYA JIKA $departmentId tersedia
            if ($departmentId) {

                // Gunakan metode map() lagi untuk memproses setiap Term Year
                $terms = $terms->map(function ($term) use ($departmentId) {

                    // Ambil data Event Sched untuk KRS (Event_Id = 1)
                    $krsSchedule = MstrEventSched::where('Term_Year_Id', $term->Term_Year_Id)
                        ->where('Department_Id', $departmentId)
                        ->where('Event_Id', 1)
                        ->first();

                    // Override nilai default NULL jika jadwal ditemukan
                    if ($krsSchedule) {
                        $term->Start_Krs_Date = $krsSchedule->Start_Date;
                        $term->End_Krs_Date = $krsSchedule->End_Date;
                    }

                    return $term;
                });
            }

            return $this->successResponse('Term Year fetched', $terms);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            // Logging error sangat disarankan di sini
            return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/term-active",
     *     tags={"Master"},
     *     summary="Get active term year",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active Term year fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Active Term Year fetched"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function termActive(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            $departmentId = null;
            if ($tokenName == 'mahasiswa-token' && $dataBearer) {
                $departmentId = $dataBearer->Department_Id;
            } elseif ($request->has('department_id')) {
                $departmentId = $request->department_id;
            }

            $query = MstrTermYear::select('*')->whereRaw('NOW() BETWEEN Start_Date AND End_Date');

            $term = $query->first();

            if ($term) {
                $term->Start_Krs_Date = null;
                $term->End_Krs_Date = null;

                if ($departmentId) {
                    $krsSchedule = MstrEventSched::where('Term_Year_Id', $term->Term_Year_Id)
                        ->where('Department_Id', $departmentId)
                        ->where('Event_Id', 1)
                        ->first();

                    if ($krsSchedule) {
                        $term->Start_Krs_Date = $krsSchedule->Start_Date;
                        $term->End_Krs_Date = $krsSchedule->End_Date;
                    }
                }
            }

            return $this->successResponse('Active Term Year fetched', $term);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/term-student",
     *     tags={"Master"},
     *     summary="Get student term history plus active term",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Student terms fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Student terms fetched"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function termStudent(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            $departmentId = null;
            if ($tokenName == 'mahasiswa-token' && $dataBearer) {
                $departmentId = $dataBearer->Department_Id;
            } elseif ($request->has('department_id')) {
                $departmentId = $request->department_id;
            }

            $query = MstrTermYear::select('*');

            // Syarat: Term yang pernah diambil MAHASISWA ATAU Term yang sedang AKTIF
            $query->where(function ($q) use ($tokenName, $dataBearer) {
                // 1. Term yang sedang AKTIF
                $q->whereRaw('NOW() BETWEEN Start_Date AND End_Date');

                // 2. Term yang pernah diikuti (Hanya jika user adalah Mahasiswa)
                if ($tokenName == 'mahasiswa-token' && $dataBearer) {
                    $followedTermIds = AcdStudentKrs::where('Student_Id', $dataBearer->Student_Id)
                        ->distinct('Term_Year_Id')
                        ->pluck('Term_Year_Id');

                    if ($followedTermIds->isNotEmpty()) {
                        $q->orWhereIn('Term_Year_Id', $followedTermIds);
                    }
                }
            });

            $terms = $query
                ->orderByRaw('CASE WHEN NOW() BETWEEN Start_Date AND End_Date THEN 1 ELSE 0 END DESC')
                ->orderBy('Term_Year_Id', 'DESC')
                ->get();

            // Mapping jadwal KRS
            $terms = $terms->map(function ($term) use ($departmentId) {
                $term->Start_Krs_Date = null;
                $term->End_Krs_Date = null;

                if ($departmentId) {
                    $krsSchedule = MstrEventSched::where('Term_Year_Id', $term->Term_Year_Id)
                        ->where('Department_Id', $departmentId)
                        ->where('Event_Id', 1)
                        ->first();

                    if ($krsSchedule) {
                        $term->Start_Krs_Date = $krsSchedule->Start_Date;
                        $term->End_Krs_Date = $krsSchedule->End_Date;
                    }
                }
                return $term;
            });

            return $this->successResponse('Student terms fetched', $terms);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
        }
    }
}
