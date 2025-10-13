<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;

class OfferedCourseController extends Controller
{
    use ApiResponseTrait;
    public function offeredCourseData(Request $request)
    {
        try {
            $request->validate([
                'department_id'    => 'nullable|integer',
                'term_year_id'     => 'nullable|integer',
                'course_id'        => 'nullable|integer',
                'course_code'      => 'nullable|string',
            ]);

            $departmentId = $request->department_id;
            $termYearId   = $request->term_year_id;
            $courseId     = $request->course_id;
            $courseCode   = $request->course_code;
            $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);

            // Query
            $query = DB::table('acd_offered_course as oc')
                ->leftJoin('mstr_class_program as cp', 'cp.Class_Prog_Id', '=', 'oc.Class_Prog_Id')
                ->leftJoin('mstr_term_year as ty', 'ty.Term_Year_Id', '=', 'oc.Term_Year_Id')
                ->leftJoin('acd_course as c', 'c.Course_Id', '=', 'oc.Course_Id')
                ->leftJoin('mstr_class as cl', 'cl.Class_Id', '=', 'oc.Class_Id')
                ->selectRaw("
                    oc.Offered_Course_Id,
                    oc.Department_Id,
                    oc.Term_Year_Id,
                    oc.Course_Id,
                    oc.Class_Id,
                    cp.Class_Program_Name,
                    ty.Term_Year_Name,
                    DATE(ty.Start_Date) as Start_Date,
                    DATE(ty.End_Date) as End_Date,
                    c.Course_Code,
                    c.Course_Name,
                    cl.Class_Name
                ")
                ->when($request->filled('department_id'), fn($q) => $q->where('oc.Department_Id', $departmentId))
                ->when($request->filled('term_year_id'), fn($q) => $q->where('oc.Term_Year_Id', $termYearId))
                ->when($request->filled('course_id'), fn($q) => $q->where('oc.Course_Id', $courseId))
                ->when($request->filled('course_code'), fn($q) => $q->where('c.Course_Code', 'like', "%{$courseCode}%"))
                ->orderByDesc('oc.Offered_Course_Id');

            if ($serverPaging) {
                $offeredCourses = $query->paginate($request->get('per_page', 20));
            } else {
                $offeredCourses = $query->cursor();
            }

            return $this->successResponse('Offered courses fetched successfully', $offeredCourses, 200, $serverPaging, $offeredCourses);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
        }
    }
}
