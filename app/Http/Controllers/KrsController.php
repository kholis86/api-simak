<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcdStudent;
use App\Models\AcdStudentKrs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;

class KrsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/student-krs",
     *     summary="Get KRS data of a student",
     *     description="Ambil data KRS berdasarkan Student_Id dan opsional Term_Year_Id",
     *     tags={"KRS"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Student_Id",
     *         in="query",
     *         required=true,
     *         description="ID mahasiswa",
     *         @OA\Schema(type="integer", example=2422)
     *     ),
     *     @OA\Parameter(
     *         name="Term_Year_Id",
     *         in="query",
     *         required=false,
     *         description="ID tahun ajaran (opsional)",
     *         @OA\Schema(type="integer", example=20241)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student KRS retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Student KRS retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="Student_Id", type="integer", example=2422),
     *                 @OA\Property(property="Full_Name", type="string", example="Budi Santoso"),
     *                 @OA\Property(property="Nim", type="string", example="202300123"),
     *                 @OA\Property(
     *                     property="Krs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="Krs_Id", type="integer", example=101),
     *                         @OA\Property(property="Term_Year_Id", type="integer", example=20241),
     *                         @OA\Property(property="Course_Id", type="integer", example=55),
     *                         @OA\Property(property="Course_Code", type="string", example="IF101"),
     *                         @OA\Property(property="Course_Name", type="string", example="Pemrograman Dasar"),
     *                         @OA\Property(property="Sks", type="integer", example=3),
     *                         @OA\Property(property="Class_Prog_Id", type="integer", example=1),
     *                         @OA\Property(property="Class_Program_Name", type="string", example="Reguler"),
     *                         @OA\Property(property="Class_Id", type="integer", example=2),
     *                         @OA\Property(property="Class_Name", type="string", example="A")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Token tidak valid atau tidak ada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Authentication token not provided"),
     *             @OA\Property(property="error", type="string", example="No bearer token found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Student not found"),
     *             @OA\Property(property="error", type="string", example="Student not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 example={"Student_Id": {"The Student_Id field is required."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[HY000]: General error ...")
     *         )
     *     )
     * )
     */

    public function studentKrs(Request $request)
    {
        try {
            $request->validate([
                'student_id'   => 'nullable',
                'term_year_id' => 'nullable|integer',
                'department_id'  => 'nullable|integer',
            ]);

            // $studentIds = $request->student_id
            //     ? (is_array($request->student_id) ? $request->student_id : [$request->student_id])
            //     : [];
            $studentIds = $request->student_id
                ? (is_array($request->student_id)
                    ? $request->student_id
                    : explode(',', $request->student_id))
                : [];
            $termYearId = $request->term_year_id;
            $departmentId = $request->department_id;
            $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);

            // Ambil mahasiswa (bisa semua, bisa beberapa)
            $studentsQuery = DB::table('acd_student')
                ->select('Student_Id', 'Full_Name', 'Nim', 'Department_Id')
                ->when(!empty($studentIds), fn($q) => $q->whereIn('Student_Id', $studentIds))
                ->when($departmentId, fn($q) => $q->where('Department_Id', $departmentId))
                ->orderBy('Student_Id');

            if ($serverPaging) {
                $students = $studentsQuery->paginate($request->get('per_page', 20));
            } else {
                $students = $studentsQuery->get();
            }

            if ($students->isEmpty()) {
                return $this->errorResponse('No students found', 'No students found', 404);
            }

            // Ambil data KRS dengan streaming
            $krsQuery = DB::table('acd_student_krs as krs')
                ->select(
                    'krs.Student_Id',
                    'krs.Krs_Id',
                    'krs.Term_Year_Id',
                    'krs.Course_Id',
                    'c.Course_Code',
                    'c.Course_Name',
                    'krs.Sks',
                    'krs.Class_Prog_Id',
                    'cp.Class_Program_Name',
                    'krs.Class_Id',
                    'cl.Class_Name'
                )
                ->join('acd_course as c', 'krs.Course_Id', '=', 'c.Course_Id')
                ->leftJoin('mstr_class_program as cp', 'krs.Class_Prog_Id', '=', 'cp.Class_Prog_Id')
                ->leftJoin('mstr_class as cl', 'krs.Class_Id', '=', 'cl.Class_Id')
                ->when(!empty($studentIds), fn($q) => $q->whereIn('krs.Student_Id', $studentIds))
                ->when($termYearId, fn($q) => $q->where('krs.Term_Year_Id', $termYearId))
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->join('acd_student as s', 'krs.Student_Id', '=', 's.Student_Id')
                        ->where('s.Department_Id', $departmentId);
                })
                ->orderBy('krs.Student_Id');

            $krsCursor = $krsQuery->cursor();

            // Group manual (hemat memori)
            $groupedKrs = [];
            foreach ($krsCursor as $row) {
                $groupedKrs[$row->Student_Id][] = [
                    'Krs_Id'            => $row->Krs_Id,
                    'Term_Year_Id'      => $row->Term_Year_Id,
                    'Course_Id'         => $row->Course_Id,
                    'Course_Code'       => $row->Course_Code,
                    'Course_Name'       => $row->Course_Name,
                    'Sks'               => $row->Sks,
                    'Class_Prog_Id'     => $row->Class_Prog_Id,
                    'Class_Program_Name' => $row->Class_Program_Name,
                    'Class_Id'          => $row->Class_Id,
                    'Class_Name'        => $row->Class_Name,
                ];
            }

            // Gabungkan hasil akhir
            $studentsData = [];
            foreach ($students as $student) {
                $krsData = $groupedKrs[$student->Student_Id] ?? [];
                $studentsData[] = [
                    'Student_Id'   => $student->Student_Id,
                    'Full_Name'    => $student->Full_Name,
                    'Nim'          => $student->Nim,
                    'Department_Id' => $student->Department_Id,
                    'Total_Krs'    => count($krsData),
                    'Krs'          => $krsData,
                ];
            }

            if ($serverPaging) {
                // Buat paginator sementara dari array manual
                $studentsDataPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $studentsData,                // items array
                    $students->total(),           // total dari query paginator
                    $students->perPage(),         // per page
                    $students->currentPage(),     // current page
                    ['path' => request()->url()]  // path untuk pagination link
                );

                return $this->successResponse(
                    'KRS fetched successfully',
                    $studentsDataPaginator, // tetap instance LengthAwarePaginator
                    200,
                    $serverPaging,
                    $studentsDataPaginator
                );
            } else {
                return $this->successResponse(
                    'KRS fetched successfully',
                    $studentsData,          // array biasa kalau tidak paging
                    200,
                    $serverPaging
                );
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
