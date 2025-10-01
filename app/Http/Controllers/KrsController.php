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
     * Ambil data KRS Mahasiswa dengan filter Student_Id & Term_Year_Id
     */
    public function studentKrs(Request $request)
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

            $krsQuery = DB::table('acd_student_krs as k')
                ->select(
                    'k.Krs_Id',
                    'k.Term_Year_Id',
                    'k.Course_Id',
                    'c.Course_Code',
                    'c.Course_Name',
                    'k.Sks',
                    'k.Class_Prog_Id',
                    'cp.Class_Program_Name',
                    'k.Class_Id',
                    'cl.Class_Name'
                )
                ->leftJoin('acd_course as c', 'k.Course_Id', '=', 'c.Course_Id')
                ->leftJoin('mstr_class_program as cp', 'k.Class_Prog_Id', '=', 'cp.Class_Prog_Id')
                ->leftJoin('mstr_class as cl', 'k.Class_Id', '=', 'cl.Class_Id')
                ->where('k.Student_Id', $studentId);

            if ($termYearId) {
                $krsQuery->where('k.Term_Year_Id', $termYearId);
            }

            $krsCursor = $krsQuery->cursor();

            $krsData = [];
            foreach ($krsCursor as $row) {
                $krsData[] = [
                    'Krs_Id'          => $row->Krs_Id,
                    'Term_Year_Id'    => $row->Term_Year_Id,
                    'Course_Id'       => $row->Course_Id,
                    'Course_Code'     => $row->Course_Code,
                    'Course_Name'     => $row->Course_Name,
                    'Sks'             => $row->Sks,
                    'Class_Prog_Id'   => $row->Class_Prog_Id,
                    'Class_Program_Name' => $row->Class_Program_Name,
                    'Class_Id'        => $row->Class_Id,
                    'Class_Name'      => $row->Class_Name,
                ];
            }

            $data = [
                'Student_Id' => $student->Student_Id,
                'Full_Name'  => $student->Full_Name,
                'Nim'        => $student->Nim,
                'Krs'        => $krsData
            ];

            return $this->successResponse('Student KRS retrieved successfully', $data, count($krsData));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
