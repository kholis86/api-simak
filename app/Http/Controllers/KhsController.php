<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;

class KhsController extends Controller
{
    use ApiResponseTrait;
    /**
     * Ambil data KHS Mahasiswa dengan filter Student_Id & Term_Year_Id
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
