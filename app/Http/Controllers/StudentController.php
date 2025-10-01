<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;

class StudentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/students",
     *     summary="Get student list",
     *     security={{"bearerAuth":{}}},
     *     tags={"Student"},
     *     @OA\Parameter(
     *         name="nim",
     *         in="query",
     *         required=false,
     *         description="Filter by Nim",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="register_number",
     *         in="query",
     *         required=false,
     *         description="Filter by Register Number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="department",
     *         in="query",
     *         required=false,
     *         description="Filter by Department Id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Student list retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="Student_Id", type="integer", example=1),
     *                     @OA\Property(property="Nim", type="string", example="20220500075"),
     *                     @OA\Property(property="Register_Number", type="string", example="123456"),
     *                     @OA\Property(property="Full_Name", type="string", example="Budi Santoso"),
     *                     @OA\Property(property="Department", type="string", example="Teknik Informatika"),
     *                     @OA\Property(property="Class_Program", type="string", example="Reguler"),
     *                     @OA\Property(property="Religion", type="string", example="Islam"),
     *                     @OA\Property(property="Marital_Status", type="string", example="Single"),
     *                     @OA\Property(property="Birth_Place", type="string", example="Palembang"),
     *                     @OA\Property(property="Birth_Date", type="string", format="date", example="2002-05-20"),
     *                     @OA\Property(property="Entry_Year", type="integer", example=2022),
     *                     @OA\Property(property="Entry_Term_Id", type="integer", example=1),
     *                     @OA\Property(property="Nisn", type="string", example="1234567890"),
     *                     @OA\Property(property="Nik", type="string", example="167xxxxxxxxxxx"),
     *                     @OA\Property(property="Email_Corporate", type="string", example="budi@sibermu.ac.id"),
     *                     @OA\Property(property="Phone_Mobile", type="string", example="081234567890")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve student list"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42S22]: Column not found...")
     *         )
     *     )
     * )
     */

    use ApiResponseTrait;

    public function studentData(Request $request)
    {
        try {
            // validasi input
            $request->validate([
                'nim' => 'nullable|integer',
                'register_number' => 'nullable|integer',
                'department_id' => 'nullable|integer',
            ]);

            $students = [];

            $query = DB::table('acd_student as s')
                ->leftJoin('mstr_department as d', 's.Department_Id', '=', 'd.Department_Id')
                ->leftJoin('mstr_class_program as cp', 's.Class_Prog_Id', '=', 'cp.Class_Prog_Id')
                ->leftJoin('mstr_religion as r', 's.Religion_Id', '=', 'r.Religion_Id')
                ->leftJoin('mstr_marital_status as m', 's.Marital_Status_Id', '=', 'm.Marital_Status_Id')
                ->select(
                    's.Student_Id',
                    's.Nim',
                    's.Register_Number',
                    's.Full_Name',
                    'd.Department_Name as Department',
                    'cp.Class_Program_Name as Class_Program',
                    'r.Religion_Name as Religion',
                    'm.Marital_Status_Type as Marital_Status',
                    's.Birth_Place',
                    's.Birth_Date',
                    's.Entry_Year_Id',
                    's.Entry_Term_Id',
                    's.Nisn',
                    's.Nik',
                    's.Email_Corporate',
                    's.Phone_Mobile'
                );

            // filter dinamis
            if ($request->filled('nim')) $query->where('s.Nim', $request->nim);
            if ($request->filled('register_number')) $query->where('s.Register_Number', $request->register_number);
            if ($request->filled('department_id')) $query->where('s.Department_Id', $request->department_id);
            if ($request->filled('entry_year')) $query->where('s.Entry_Year_Id', $request->entry_year);

            // gunakan cursor untuk streaming → 1 query → memory ringan
            foreach ($query->orderBy('s.Student_Id')->cursor() as $student) {
                $students[] = [
                    'Student_Id'       => $student->Student_Id,
                    'Nim'              => $student->Nim,
                    'Register_Number'  => $student->Register_Number,
                    'Full_Name'        => $student->Full_Name,
                    'Department'       => $student->Department,
                    'Class_Program'    => $student->Class_Program,
                    'Religion'         => $student->Religion,
                    'Marital_Status'   => $student->Marital_Status,
                    'Birth_Place'      => $student->Birth_Place,
                    'Birth_Date'       => $student->Birth_Date,
                    'Entry_Year'       => $student->Entry_Year_Id,
                    'Entry_Term_Id'    => $student->Entry_Term_Id,
                    'Nisn'             => $student->Nisn,
                    'Nik'              => $student->Nik,
                    'Email_Corporate'  => $student->Email_Corporate,
                    'Phone_Mobile'     => $student->Phone_Mobile,
                ];
            }

            return $this->successResponse('Student list retrieved successfully', $students, count($students));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
