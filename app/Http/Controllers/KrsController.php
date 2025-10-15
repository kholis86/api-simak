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
     *     summary="Get Student KRS Data",
     *     description="Menampilkan data KRS (Kartu Rencana Studi) mahasiswa berdasarkan filter student_id, term_year_id, dan department_id.",
     *     operationId="studentKrs",
     *     security={{"bearerAuth":{}}},
     *     tags={"Academic"},
     *
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         required=false,
     *         description="Bisa single ID, array, atau daftar ID dipisahkan koma (contoh: 1001,1002,1003)",
     *         @OA\Schema(type="string", example="1001,1002")
     *     ),
     *     @OA\Parameter(
     *         name="term_year_id",
     *         in="query",
     *         required=false,
     *         description="Filter berdasarkan tahun ajaran",
     *         @OA\Schema(type="integer", example=20241)
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=false,
     *         description="Filter berdasarkan department",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="server_paging",
     *         in="query",
     *         required=false,
     *         description="Aktifkan pagination server-side (true/false)",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (hanya jika server_paging=true)",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="KRS fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="KRS fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={
     *                     "current_page": 1,
     *                     "per_page": 20,
     *                     "total": 2,
     *                     "data": {
     *                         {
     *                             "Student_Id": 1001,
     *                             "Full_Name": "Budi Santoso",
     *                             "Nim": "21010001",
     *                             "Department_Id": 5,
     *                             "Total_Krs": 3,
     *                             "Krs": {
     *                                 {
     *                                     "Krs_Id": 1,
     *                                     "Term_Year_Id": 20241,
     *                                     "Course_Id": 201,
     *                                     "Course_Code": "IF201",
     *                                     "Course_Name": "Algoritma dan Pemrograman",
     *                                     "Sks": 3,
     *                                     "Class_Prog_Id": 2,
     *                                     "Class_Program_Name": "Reguler Pagi",
     *                                     "Class_Id": 1,
     *                                     "Class_Name": "A"
     *                                 },
     *                                 {
     *                                     "Krs_Id": 2,
     *                                     "Term_Year_Id": 20241,
     *                                     "Course_Id": 205,
     *                                     "Course_Code": "IF205",
     *                                     "Course_Name": "Basis Data",
     *                                     "Sks": 3,
     *                                     "Class_Prog_Id": 2,
     *                                     "Class_Program_Name": "Reguler Pagi",
     *                                     "Class_Id": 2,
     *                                     "Class_Name": "B"
     *                                 }
     *                             }
     *                         }
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No students found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="No students found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={"student_id": {"The student_id field must be an integer."}}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Internal server error")
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
