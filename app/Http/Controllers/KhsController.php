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
     *     tags={"KHS"},
     *     summary="Get student KHS",
     *     description="Ambil data KHS mahasiswa berdasarkan Student_Id (wajib) dan Term_Year_Id (opsional)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Student_Id",
     *         in="query",
     *         required=true,
     *         description="ID mahasiswa",
     *         @OA\Schema(type="integer", example=12345)
     *     ),
     *     @OA\Parameter(
     *         name="Term_Year_Id",
     *         in="query",
     *         required=false,
     *         description="ID tahun akademik (opsional)",
     *         @OA\Schema(type="integer", example=20241)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student KHS retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Student KHS retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="Student_Id", type="integer", example=12345),
     *                 @OA\Property(property="Full_Name", type="string", example="Ahmad Budi"),
     *                 @OA\Property(property="Nim", type="string", example="210101001"),
     *                 @OA\Property(
     *                     property="Khs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="Krs_Id", type="integer", example=1001),
     *                         @OA\Property(property="Course_Id", type="integer", example=2001),
     *                         @OA\Property(property="Course_Code", type="string", example="IF101"),
     *                         @OA\Property(property="Course_Name", type="string", example="Algoritma & Pemrograman"),
     *                         @OA\Property(property="Sks", type="integer", example=3),
     *                         @OA\Property(property="Grade_Letter_Id", type="string", example="A"),
     *                         @OA\Property(property="Weight_Value", type="number", format="float", example=4.00),
     *                         @OA\Property(property="Bnk_Value", type="number", format="float", example=12.00)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=5)
     *             )
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
     *             @OA\Property(property="data", type="array", @OA\Items())
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
     *             @OA\Property(property="errors", type="object",
     *                 example={"Student_Id": {"The Student_Id field is required."}}
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items())
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
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42S22]: Column not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */

    public function studentKhs(Request $request)
    {
        try {
            $request->validate([
                'Student_Id'   => 'required|integer',
                'Term_Year_Id' => 'nullable|integer',
            ]);

            $studentId = $request->Student_Id;
            $termYearId = $request->Term_Year_Id;

            $student = DB::table('acd_student')
                ->select('Student_Id', 'Full_Name', 'Nim')
                ->where('Student_Id', $studentId)
                ->first();

            if (!$student) {
                return $this->errorResponse('Student not found', 'Student not found', 404);
            }

            $khsQuery = DB::table('acd_student_khs as khs')
                ->select(
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
                    'khs.Bnk_Value',
                    'khs.Sks as Sks_Khs'
                )
                ->join('acd_grade_letter as gl', 'khs.Grade_Letter_Id', '=', 'gl.Grade_Letter_Id')
                ->join('acd_student_krs as krs', 'khs.Krs_Id', '=', 'krs.Krs_Id')
                ->join('acd_course as c', 'krs.Course_Id', '=', 'c.Course_Id')
                ->leftJoin('mstr_class_program as cp', 'krs.Class_Prog_Id', '=', 'cp.Class_Prog_Id')
                ->leftJoin('mstr_class as cl', 'krs.Class_Id', '=', 'cl.Class_Id')
                ->where('krs.Student_Id', $studentId);

            if ($termYearId) {
                $khsQuery->where('k.Term_Year_Id', $termYearId);
            }

            $krsCursor = $khsQuery->cursor();

            $krsData = [];
            foreach ($krsCursor as $row) {
                $krsData[] = [
                    'Krs_Id'            => $row->Krs_Id,
                    'Term_Year_Id'      => $row->Term_Year_Id,
                    'Course_Id'         => $row->Course_Id,
                    'Course_Code'       => $row->Course_Code,
                    'Course_Name'       => $row->Course_Name,
                    'Sks'               => $row->Sks,
                    'Class_Prog_Id'     => $row->Class_Prog_Id,
                    'Class_Program_Name'   => $row->Class_Program_Name,
                    'Class_Id'          => $row->Class_Id,
                    'Class_Name'        => $row->Class_Name,
                    'Grade_Letter'        => $row->Grade_Letter,
                    'Weight_Value'      => $row->Weight_Value,
                    'Bnk_Value'         => $row->Bnk_Value,
                    // 'Sks_Khs'           => $row->Sks_Khs,
                ];
            }

            $data = [
                'Student_Id' => $student->Student_Id,
                'Full_Name'  => $student->Full_Name,
                'Nim'        => $student->Nim,
                'Khs'        => $krsData
            ];

            return $this->successResponse('Student KHS retrieved successfully', $data, count($krsData));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
