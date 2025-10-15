<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;

class OfferedCourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/offered-course",
     *     summary="Get Offered Course Data",
     *     description="Menampilkan daftar mata kuliah yang ditawarkan berdasarkan filter department, term, course, dan lainnya.",
     *     operationId="offeredCourseData",
     *     security={{"bearerAuth":{}}},
     *     tags={"Academic"},
     *
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=false,
     *         description="ID dari department (opsional)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="term_year_id",
     *         in="query",
     *         required=false,
     *         description="ID tahun ajaran (opsional)",
     *         @OA\Schema(type="integer", example=20241)
     *     ),
     *     @OA\Parameter(
     *         name="course_id",
     *         in="query",
     *         required=false,
     *         description="ID mata kuliah (opsional)",
     *         @OA\Schema(type="integer", example=120)
     *     ),
     *     @OA\Parameter(
     *         name="course_code",
     *         in="query",
     *         required=false,
     *         description="Kode mata kuliah (opsional, pencarian partial)",
     *         @OA\Schema(type="string", example="IF202")
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
     *         description="Jumlah item per halaman (hanya jika server_paging=true)",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Offered courses fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Offered courses fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=125),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="Offered_Course_Id", type="integer", example=150),
     *                         @OA\Property(property="Department_Id", type="integer", example=2),
     *                         @OA\Property(property="Term_Year_Id", type="integer", example=20241),
     *                         @OA\Property(property="Course_Id", type="integer", example=120),
     *                         @OA\Property(property="Class_Id", type="integer", example=5),
     *                         @OA\Property(property="Class_Program_Name", type="string", example="Reguler Pagi"),
     *                         @OA\Property(property="Term_Year_Name", type="string", example="2024/2025 Ganjil"),
     *                         @OA\Property(property="Start_Date", type="string", format="date", example="2024-09-01"),
     *                         @OA\Property(property="End_Date", type="string", format="date", example="2025-01-31"),
     *                         @OA\Property(property="Course_Code", type="string", example="IF202"),
     *                         @OA\Property(property="Course_Name", type="string", example="Pemrograman Lanjut"),
     *                         @OA\Property(property="Class_Name", type="string", example="A")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Something went wrong",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */

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
