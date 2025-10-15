<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;

class KhsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/student-khs",
     *     summary="Get Student KHS Data",
     *     description="Mengambil data KHS (Kartu Hasil Studi) mahasiswa berdasarkan filter student_id, term_year_id, dan department_id.",
     *     operationId="studentKhs",
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
     *         description="KHS fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="KHS fetched successfully"),
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
     *                             "Total_Khs": 3,
     *                             "Khs": {
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
     *                                     "Class_Name": "A",
     *                                     "Grade_Letter": "A",
     *                                     "Weight_Value": 4.00,
     *                                     "Bnk_Value": 90
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
     *                                     "Class_Name": "B",
     *                                     "Grade_Letter": "B+",
     *                                     "Weight_Value": 3.5,
     *                                     "Bnk_Value": 85
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
     *                 example={"term_year_id": {"The term_year_id field must be an integer."}}
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

    public function studentKhs(Request $request)
    {
        try {
            $request->validate([
                'student_id'     => 'nullable', // bisa kosong (ambil semua) atau integer/array
                'term_year_id'   => 'nullable|integer',
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
            $termYearId   = $request->term_year_id;
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

            // Ambil data KHS
            $khsQuery = DB::table('acd_student_khs as khs')
                ->select(
                    'krs.Student_Id',
                    'khs.Krs_Id',
                    'krs.Term_Year_Id',
                    'krs.Course_Id',
                    'c.Course_Code',
                    'c.Course_Name',
                    'krs.Sks',
                    'krs.Class_Prog_Id',
                    'cp.Class_Program_Name',
                    'krs.Class_Id',
                    'cl.Class_Name',
                    'gl.Grade_Letter',
                    'khs.Weight_Value',
                    'khs.Bnk_Value'
                )
                ->join('acd_grade_letter as gl', 'khs.Grade_Letter_Id', '=', 'gl.Grade_Letter_Id')
                ->join('acd_student_krs as krs', 'khs.Krs_Id', '=', 'krs.Krs_Id')
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

            // Gunakan cursor() agar streaming / hemat memori
            $khsCursor = $khsQuery->cursor();

            // Grouping manual agar tidak semua data ditampung dulu
            $groupedKhs = [];
            foreach ($khsCursor as $row) {
                $groupedKhs[$row->Student_Id][] = [
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
                    'Grade_Letter'      => $row->Grade_Letter,
                    'Weight_Value'      => $row->Weight_Value,
                    'Bnk_Value'         => $row->Bnk_Value,
                ];
            }

            // Gabungkan mahasiswa dengan data KHS-nya
            $studentsData = [];
            foreach ($students as $student) {
                $khsData = $groupedKhs[$student->Student_Id] ?? [];
                $studentsData[] = [
                    'Student_Id'   => $student->Student_Id,
                    'Full_Name'    => $student->Full_Name,
                    'Nim'          => $student->Nim,
                    'Department_Id' => $student->Department_Id,
                    'Total_Khs'    => count($khsData),
                    'Khs'          => $khsData,
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
                    'KHS fetched successfully',
                    $studentsDataPaginator, // tetap instance LengthAwarePaginator
                    200,
                    $serverPaging,
                    $studentsDataPaginator
                );
            } else {
                return $this->successResponse(
                    'KHS fetched successfully',
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
