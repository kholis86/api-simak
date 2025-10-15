<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Helpers\TanggalIndo;

class StudentControllerOLD extends Controller
{

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

            // filter
            if ($request->filled('student_id')) $query->where('s.Student_Id', $request->student_id);
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
