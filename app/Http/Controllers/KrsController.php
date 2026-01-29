<?php

namespace App\Http\Controllers;

use App\Helpers\CheckJenisToken;
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

            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();
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
            $studentsQuery = DB::table('acd_student');
            if ($tokenName == 'mahasiswa-token') {
                $studentsQuery->where('Student_Id', $dataBearer->Student_Id);
            }
            $studentsQuery->select('Student_Id', 'Full_Name', 'Nim', 'Department_Id')
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

    // // Hanya mahasiswa yang boleh input KRS
    // if ($tokenName !== 'mahasiswa-token') {
    //     return $this->errorResponse(
    //         'Forbidden',
    //         'Anda tidak memiliki akses untuk input KRS',
    //         403
    //     );
    // }

    // // Set Student_Id dari token mahasiswa
    // $studentId = $dataBearer->Student_Id;

    // $studentId = ($tokenName == 'mahasiswa-token')
    //             ? $dataBearer->Student_Id
    //             : $request->student_id;


    //         if (!$studentId) {
    //             return $this->errorResponse(
    //                 'Student_Id required',
    //                 'Student_Id missing',
    //                 422
    //             );
    //         }

    /**
     * @OA\Post(
     *     path="/api/post-krs",
     *     tags={"Academic"},
     *     summary="Submit KRS",
     *     description="Input Kartu Rencana Studi (KRS) untuk mahasiswa.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"term_year_id","offered_course_ids"},
     *             @OA\Property(property="term_year_id", type="integer", example=20241),
     *             @OA\Property(property="offered_course_ids", type="array", @OA\Items(type="integer", example=150)),
     *             @OA\Property(property="student_id", type="integer", example=1001, description="Wajib jika menggunakan api-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="KRS successfully submitted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="KRS successfully submitted")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed or Period closed")
     * )
     */
    public function postKrs(Request $request)
    {
        DB::beginTransaction(); // Pastikan ini di-uncomment jika ingin transaksi aktif

        try {
            // 1. VALIDASI INPUT AWAL
            $request->validate([
                'term_year_id'              => 'required|integer',
                'offered_course_ids'        => 'required|array|min:1',
                'offered_course_ids.*'      => 'required|integer',
            ]);

            // 0. CEK PERIODE KRS
            $periodError = $this->validateKrsPeriod($request->term_year_id);
            if ($periodError) {
                DB::rollBack();
                return $this->errorResponse('KRS Period Closed', $periodError, 422);
            }

            $tokenName  = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            $studentId = ($tokenName == 'mahasiswa-token')
                ? $dataBearer->Student_Id
                : $request->student_id;

            if (!$studentId) {
                DB::rollBack();
                return $this->errorResponse('Student_Id required', 'Student_Id missing', 422);
            }

            $termYearId = $request->term_year_id;
            $offeredCourseIds = $request->offered_course_ids;

            $maxSKS     = 24;
            $now        = now();

            // 2. AMBIL DETAIL MATA KULIAH DARI acd_offered_course DENGAN JOIN acd_course
            // Menggunakan alias aoc dan c untuk menghindari ambiguitas kolom
            $offeredCourses = DB::table('acd_offered_course as aoc')
                // Melakukan JOIN dengan acd_course untuk mengambil dan menghitung SKS
                ->join('acd_course as c', 'aoc.Course_Id', '=', 'c.Course_Id')
                ->where('aoc.Term_Year_Id', $termYearId)
                ->whereIn('aoc.Offered_Course_id', $offeredCourseIds)
                ->select(
                    'aoc.Offered_Course_id',
                    'aoc.Course_Id',
                    'aoc.Class_Id',
                    'aoc.Class_Prog_Id',
                    'c.Course_Code',
                    // Menghitung total SKS dari 4 kolom di acd_course (c)
                    DB::raw('(
                        COALESCE(c.Sks_Tm, 0) + 
                        COALESCE(c.Sks_Prak, 0) + 
                        COALESCE(c.Sks_Prak_Lap, 0) + 
                        COALESCE(c.Sks_Sim, 0)
                    ) as Sks')
                )
                // ->lockForUpdate() 
                ->get();

            // Cek apakah semua Offered_Course_id yang diinput valid
            $foundIds = $offeredCourses->pluck('Offered_Course_id')->toArray();
            $missingIds = array_diff($offeredCourseIds, $foundIds);

            if (!empty($missingIds)) {
                DB::rollBack();
                return $this->errorResponse(
                    'Invalid Offered Course ID',
                    'Offered_Course_id tidak valid atau tidak tersedia di Term/Tahun Ajaran ini: ' . implode(', ', $missingIds),
                    422
                );
            }

            $offeredCoursesMap  = $offeredCourses->keyBy('Offered_Course_id');
            $insertData         = [];
            $totalNewSks        = 0;

            // 3. AMBIL KRS EXISTING
            $existingKrs = DB::table('acd_student_krs')
                ->where('Student_Id', $studentId)
                ->where('Term_Year_Id', $termYearId)
                ->get();

            $existingCourseIds  = $existingKrs->pluck('Course_Id')->toArray();
            $existingTotalSks   = $existingKrs->sum('Sks');

            // 4. LOOP DAN VALIDASI DUPLIKAT
            foreach ($offeredCourseIds as $offeredId) {
                // Sks diambil dari $courseDetail->Sks (hasil perhitungan join)
                $courseDetail = $offeredCoursesMap->get($offeredId);

                if ($courseDetail) {
                    // (A) Cek duplicate course
                    if (in_array($courseDetail->Course_Id, $existingCourseIds)) {
                        DB::rollBack();
                        return $this->errorResponse(
                            'Duplicate Course',
                            'Mata kuliah sudah diambil sebelumnya: ' . $courseDetail->Course_Code,
                            422
                        );
                    }

                    // Total SKS dihitung berdasarkan kolom 'Sks' hasil DB::raw()
                    $totalNewSks += $courseDetail->Sks;

                    // Siapkan data untuk INSERT
                    $insertData[] = [
                        'Student_Id'    => $studentId,
                        'Term_Year_Id'  => $termYearId,
                        'Course_Id'     => $courseDetail->Course_Id,
                        'Class_Prog_Id' => $courseDetail->Class_Prog_Id,
                        'Class_Id'      => $courseDetail->Class_Id,
                        'Sks'           => $courseDetail->Sks, // Menggunakan Sks yang sudah dihitung
                        'Amount'        => 0,
                        'Krs_Date'      => $now,
                        'Created_By'    => $dataBearer->User_Id ?? 'system',
                        'Created_Date'  => $now,
                        'Modified_By'   => $dataBearer->User_Id ?? 'system',
                        'Modified_Date' => $now,
                    ];

                    $existingCourseIds[] = $courseDetail->Course_Id;
                }
            }

            // 5. VALIDASI TOTAL SKS
            if (($existingTotalSks + $totalNewSks) > $maxSKS) {
                DB::rollBack();
                return $this->errorResponse(
                    'SKS Over Limit',
                    'Total SKS melebihi batas maksimum ' . $maxSKS,
                    422
                );
            }

            // 6. OPERASI INSERT
            DB::table('acd_student_krs')->insert($insertData);

            DB::commit();

            return $this->successResponse(
                'KRS successfully submitted',
                [
                    'total_inserted_items'  => count($insertData),
                    'total_sks_now'         => $existingTotalSks + $totalNewSks,
                    'max_sks'               => $maxSKS
                ],
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/delete-krs",
     *     tags={"Academic"},
     *     summary="Delete KRS",
     *     description="Hapus satu item KRS mahasiswa.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"krs_id"},
     *             @OA\Property(property="krs_id", type="integer", example=1),
     *             @OA\Property(property="student_id", type="integer", example=1001, description="Wajib jika menggunakan api-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="KRS deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="KRS deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="KRS not found")
     * )
     */
    public function deleteKrs(Request $request)
    {
        try {
            $request->validate([
                'krs_id' => 'required|integer',
                'student_id' => 'nullable|integer',
            ]);

            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            // Tentukan Student_Id berdasarkan token
            if ($tokenName === 'mahasiswa-token') {
                $studentId = $dataBearer->Student_Id;
            } elseif ($tokenName === 'api-token') {
                // Admin wajib kirim student_id
                if (!$request->student_id) {
                    return $this->errorResponse(
                        'Student_Id required',
                        'Admin must provide Student_Id on delete KRS',
                        422
                    );
                }
                $studentId = $request->student_id;
            } else {
                return $this->errorResponse(
                    'Forbidden',
                    'Anda tidak memiliki akses untuk hapus KRS',
                    403
                );
            }

            // Cari Data KRS dulu untuk dapat Term_Year_Id
            $krs = DB::table('acd_student_krs')
                ->where('Krs_Id', $request->krs_id)
                ->where('Student_Id', $studentId)
                ->first();

            if (!$krs) {
                return $this->errorResponse(
                    'Not Found',
                    'Data KRS tidak ditemukan atau bukan milik mahasiswa terkait',
                    404
                );
            }

            // CEK PERIODE KRS
            $periodError = $this->validateKrsPeriod($krs->Term_Year_Id);
            if ($periodError) {
                return $this->errorResponse('KRS Period Closed', $periodError, 422);
            }

            $deleted = DB::table('acd_student_krs')
                ->where('Krs_Id', $request->krs_id)
                ->delete();

            return $this->successResponse(
                'KRS deleted successfully',
                [],
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * Helper untuk validasi periode KRS
     * Mengembalikan pesan error (string) jika tidak valid, atau null jika valid.
     */
    private function validateKrsPeriod($termYearId)
    {
        $term = DB::table('mstr_term_year')->where('Term_Year_Id', $termYearId)->first();

        if (!$term) {
            return 'Term Year ID tidak ditemukan.';
        }

        $now = date('Y-m-d');
        if ($now < $term->Start_Date || $now > $term->End_Date) {
            return 'Periode pengisian/perubahan KRS untuk semester ini sudah ditutup. (' . $term->Start_Date . ' s/d ' . $term->End_Date . ')';
        }

        return null;
    }
}
