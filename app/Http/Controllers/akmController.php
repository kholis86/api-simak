<?php

namespace App\Http\Controllers;

use App\Helpers\CheckJenisToken;
use App\Models\AcdStudentKrs;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class akmController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/akm",
     *     summary="Get Academic Performance (AKM) data of students",
     *     tags={"Academic"},
     *     description="Menampilkan data AKM (Aktivitas Kuliah Mahasiswa) per mahasiswa berdasarkan filter department, tahun masuk, dan pagination opsional.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         required=false,
     *         description="ID Mahasiswa (opsional, bisa untuk 1 mahasiswa)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="nim",
     *         in="query",
     *         required=false,
     *         description="Nomor Induk Mahasiswa (opsional)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=false,
     *         description="Filter berdasarkan ID departemen",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="entry_year",
     *         in="query",
     *         required=false,
     *         description="Tahun masuk mahasiswa (Entry_Year_Id)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="server_paging",
     *         in="query",
     *         required=false,
     *         description="Aktifkan pagination server-side (true/false)",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman jika server_paging = true",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="AKM fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="AKM fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="nama", type="string", example="Budi Santoso"),
     *                     @OA\Property(property="nim", type="string", example="22011001"),
     *                     @OA\Property(
     *                         property="akm",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="term_year_id", type="integer", example=20241),
     *                             @OA\Property(property="sks", type="integer", example=20),
     *                             @OA\Property(property="sks_kumulatif", type="integer", example=120),
     *                             @OA\Property(property="bnk_total", type="number", format="float", example=65.5),
     *                             @OA\Property(property="ipk", type="number", format="float", example=3.25),
     *                             @OA\Property(property="ipk_kumulatif", type="number", format="float", example=3.22)
     *                         )
     *                     )
     *                 )
     *             )
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
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Something went wrong"),
     *             @OA\Property(property="data", type="string", example="SQLSTATE[42S22]: Column not found: 1054 Unknown column...")
     *         )
     *     )
     * )
     */

    public function akmData(Request $request)
    {
        try {
            $request->validate([
                'department_id'    => 'nullable|integer',
                'term_year_id'     => 'nullable|integer',
                'course_id'        => 'nullable|integer',
                'course_code'      => 'nullable|string',
            ]);

            $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);
            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            $studentsPage = DB::table('acd_student as s');
            if ($tokenName == 'mahasiswa-token') {
                $studentsPage->where('s.Student_Id', $dataBearer->Student_Id);
            }
            $studentsPage->when($request->filled('student_id'), fn($q) => $q->where('s.Student_Id', $request->student_id))
                ->when($request->filled('nim'), fn($q) => $q->where('s.Nim', $request->nim))
                ->when($request->filled('department_id'), fn($q) => $q->where('s.Department_Id', $request->department_id))
                ->when($request->filled('entry_year'), fn($q) => $q->where('s.Entry_Year_Id', $request->entry_year))
                ->orderBy('s.Student_Id');

            if ($serverPaging) {
                $studentsPage = $studentsPage->paginate($request->get('per_page', 20));
                $studentsCollection = collect($studentsPage->items());
            } else {
                $studentsCollection = $studentsPage->get();
                $studentsPage = null;
            }

            $studentIds = $studentsCollection->pluck('Student_Id');

            $transcripts = DB::table('acd_transcript')
                ->whereIn('Student_Id', $studentIds)
                ->select('Student_Id', 'Term_Year_Id', DB::raw('SUM(Sks) as sks_semester'), DB::raw('SUM(Bnk_Value) as bnk_total'))
                ->groupBy('Student_Id', 'Term_Year_Id')
                ->orderBy('Term_Year_Id')
                ->get();

            $transcriptsByStudent = $transcripts->groupBy('Student_Id');

            $studentsData = $studentsCollection->map(function ($student) use ($transcriptsByStudent) {
                $studentTranscripts = $transcriptsByStudent->get($student->Student_Id, collect());
                $sksKumulatif = 0;
                $totalNilaiKumulatif = 0;

                $akm = $studentTranscripts->map(function ($t) use (&$sksKumulatif, &$totalNilaiKumulatif) {
                    $sksKumulatif += $t->sks_semester;
                    $totalNilaiKumulatif += $t->bnk_total;

                    return [
                        'term_year_id' => $t->Term_Year_Id,
                        'sks' => $t->sks_semester,
                        'sks_kumulatif' => $sksKumulatif,
                        'bnk_total' => $t->bnk_total,
                        'ipk' => $t->sks_semester ? round($t->bnk_total / $t->sks_semester, 2) : 0,
                        'ipk_kumulatif' => $sksKumulatif ? round($totalNilaiKumulatif / $sksKumulatif, 2) : 0
                    ];
                })->reverse()->values();

                return [
                    'nama' => $student->Full_Name,
                    'nim' => $student->Nim,
                    'akm' => $akm
                ];
            });

            if ($serverPaging && $studentsPage instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $studentsDataPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $studentsData,
                    $studentsPage->total(),
                    $studentsPage->perPage(),
                    $studentsPage->currentPage(),
                    ['path' => request()->url()]
                );

                return $this->successResponse(
                    'AKM fetched successfully',
                    $studentsDataPaginator,
                    200,
                    $serverPaging,
                    $studentsDataPaginator
                );
            }

            return $this->successResponse(
                'AKM fetched successfully',
                $studentsData,
                200,
                $serverPaging
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/akmBySemester",
     *     tags={"Academic"},
     *     summary="Get AKM by Semester",
     *     description="Ambil data AKM yang dikelompokkan per semester.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="term_year_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="query",
     *         required=true,
     *         description="Gunakan 1 atau 2 untuk versi query berbeda",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function akmBySemesterData(Request $request)
    {
        if ($request->checkpoint == 1) {
            try {
                $request->validate([
                    'student_id'    => 'nullable|integer',
                    'nim'           => 'nullable|string',
                    'entry_year_id' => 'nullable|integer',
                    'term_year_id'  => 'nullable|integer',
                    'department_id' => 'nullable|integer',
                    'per_page'      => 'nullable|integer'
                ]);

                $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);
                $perPage = $request->get('per_page', 20);
                $targetTermYear = $request->term_year_id;
                $tokenName = CheckJenisToken::getName($request);
                $dataBearer = $request->user();

                // 1. Ambil data mahasiswa (Pastikan Entry_Term_Id diambil)
                $studentQuery = DB::table('acd_student as s')
                    ->select('s.Student_Id', 's.Full_Name', 's.Nim', 's.Entry_Year_Id', 's.Entry_Term_Id', 's.Department_Id');

                if ($tokenName == 'mahasiswa-token') {
                    $studentQuery->where('s.Student_Id', $dataBearer->Student_Id);
                }
                $studentQuery->when($request->student_id, fn($q) => $q->where('s.Student_Id', $request->student_id));
                $studentQuery->when($request->nim, fn($q) => $q->where('s.Nim', $request->nim));
                $studentQuery->when($request->entry_year_id, fn($q) => $q->where('s.Entry_Year_Id', $request->entry_year_id));
                $studentQuery->when($request->department_id, fn($q) => $q->where('s.Department_Id', $request->department_id));

                $students = $studentQuery->get();
                $allSemesters = DB::table('mstr_term_year')->orderBy('Term_Year_Id', 'ASC')->get();

                // 2. Ambil data nilai
                $allKrsData = DB::table('acd_student_krs as k')
                    ->leftJoin('acd_student_khs as h', 'h.Krs_Id', '=', 'k.Krs_Id')
                    ->leftJoin('acd_transcript as t', 't.Khs_Id', '=', 'h.Khs_Id')
                    ->select(
                        'k.Student_Id',
                        'k.Term_Year_Id',
                        DB::raw('SUM(COALESCE(t.Sks, k.Sks)) as sks'),
                        DB::raw('SUM(COALESCE(t.Bnk_Value, 0)) as bnk_total')
                    )
                    ->whereIn('k.Student_Id', $students->pluck('Student_Id'))
                    ->groupBy('k.Student_Id', 'k.Term_Year_Id')
                    ->get()
                    ->groupBy('Student_Id');

                $processedData = collect();

                foreach ($students as $student) {
                    $cumulativeSks = 0.0;
                    $cumulativeBnkTotal = 0.0;
                    $studentKrs = $allKrsData->get($student->Student_Id, collect())->keyBy('Term_Year_Id');

                    // Threshold: Gabungkan Tahun + Term (Contoh: 2021 + 2 = 20212)
                    $entryThreshold = (int) ($student->Entry_Year_Id . $student->Entry_Term_Id);

                    foreach ($allSemesters as $semester) {
                        $currentTerm = (int) $semester->Term_Year_Id;

                        // Lewati semester sebelum mahasiswa masuk
                        if ($currentTerm < $entryThreshold) {
                            continue;
                        }

                        $krsSem = $studentKrs->get($semester->Term_Year_Id);

                        $sks = $krsSem ? (float)$krsSem->sks : 0.0;
                        $bnkTotal = $krsSem ? (float)$krsSem->bnk_total : 0.0;

                        $ips = $sks > 0 ? round($bnkTotal / $sks, 2) : 0.0;

                        $cumulativeSks += $sks;
                        $cumulativeBnkTotal += $bnkTotal;
                        $ipk = $cumulativeSks > 0 ? round($cumulativeBnkTotal / $cumulativeSks, 2) : 0.0;

                        $processedData->push((object)[
                            'Student_Id'    => $student->Student_Id,
                            'Full_Name'     => $student->Full_Name,
                            'Nim'           => $student->Nim,
                            'Entry_Year_Id' => $student->Entry_Year_Id,
                            'Department_Id' => $student->Department_Id,
                            'Term_Year_Id'  => $semester->Term_Year_Id,
                            'sks'           => $sks,
                            'bnk_total'     => $bnkTotal, // Pastikan property ini masuk ke object
                            'ips'           => $ips,
                            'ipk'           => $ipk,
                            'sks_total'     => $cumulativeSks,
                            'status'        => $sks > 0 ? 'Aktif' : 'Cuti',
                        ]);
                    }
                }

                // 3. Filter Tampilan
                if ($targetTermYear) {
                    $processedData = $processedData->where('Term_Year_Id', $targetTermYear);
                }

                $processedData = $processedData->sortByDesc('Term_Year_Id');

                // 4. Grouping dengan pengecekan property yang aman
                $grouped = $processedData->groupBy('Term_Year_Id')->map(function ($items) {
                    return $items->map(function ($item) {
                        return [
                            'student_id'    => $item->Student_Id,
                            'nama'          => $item->Full_Name,
                            'nim'           => $item->Nim,
                            'entry_year_id' => $item->Entry_Year_Id,
                            'sks'           => $item->sks,
                            'sks_total'     => $item->sks_total,
                            'bnk_total'     => $item->bnk_total ?? 0, // Fallback ke 0 jika tidak ada
                            'ips'           => $item->ips,
                            'ipk'           => $item->ipk,
                            'status'        => $item->status,
                            'department_id' => $item->Department_Id,
                        ];
                    })->values();
                });

                if ($serverPaging) {
                    $total = $grouped->count();
                    $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
                    $pagedCollection = $grouped->forPage($currentPage, $perPage);
                    $paged = new \Illuminate\Pagination\LengthAwarePaginator($pagedCollection, $total, $perPage, $currentPage, ['path' => request()->url()]);
                    return $this->successResponse('AKM by semester fetched', $paged, 200, true, $paged);
                }

                return $this->successResponse('AKM by semester fetched', $grouped);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->errorResponse('Validation failed', $e->errors(), 422);
            } catch (\Exception $e) {
                return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
            }
        } elseif ($request->checkpoint == 2) {
            try {
                $request->validate([
                    'student_id'    => 'nullable|integer',
                    'nim'           => 'nullable|string',
                    'entry_year_id' => 'nullable|integer',
                    'term_year_id'  => 'nullable|integer',
                    'department_id' => 'nullable|integer',
                    'per_page'      => 'nullable|integer'
                ]);

                $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);
                $perPage = $request->get('per_page', 20);
                $targetTermYear = $request->term_year_id;
                $tokenName = CheckJenisToken::getName($request);
                $dataBearer = $request->user();

                // 1. Ambil data Master Mahasiswa (Wajib ambil Entry_Year_Id dan Entry_Term_Id)
                $studentQuery = DB::table('acd_student as s')
                    ->select('s.Student_Id', 's.Full_Name', 's.Nim', 's.Entry_Year_Id', 's.Entry_Term_Id', 's.Department_Id');

                if ($tokenName == 'mahasiswa-token') {
                    $studentQuery->where('s.Student_Id', $dataBearer->Student_Id);
                }
                $studentQuery->when($request->student_id, fn($q) => $q->where('s.Student_Id', $request->student_id));
                $studentQuery->when($request->nim, fn($q) => $q->where('s.Nim', $request->nim));
                $studentQuery->when($request->entry_year_id, fn($q) => $q->where('s.Entry_Year_Id', $request->entry_year_id));
                $studentQuery->when($request->department_id, fn($q) => $q->where('s.Department_Id', $request->department_id));

                $students = $studentQuery->get();
                $studentIds = $students->pluck('Student_Id');

                // 2. Ambil Master Semester untuk looping baris yang "kosong"
                $allSemesters = DB::table('mstr_term_year')->orderBy('Term_Year_Id', 'ASC')->get();

                // 3. Ambil Data KRS & Nilai yang ada di database
                $krsData = DB::table('acd_student_krs as k')
                    ->leftJoin('acd_student_khs as h', 'h.Krs_Id', '=', 'k.Krs_Id')
                    ->leftJoin('acd_transcript as t', 't.Khs_Id', '=', 'h.Khs_Id')
                    ->select(
                        'k.Student_Id',
                        'k.Term_Year_Id',
                        DB::raw('SUM(COALESCE(t.Sks, k.Sks)) as sks'),
                        DB::raw('SUM(COALESCE(t.Bnk_Value, 0)) as bnk_total')
                    )
                    ->whereIn('k.Student_Id', $studentIds)
                    ->groupBy('k.Student_Id', 'k.Term_Year_Id')
                    ->get()
                    ->groupBy('Student_Id');

                $processedData = collect();

                foreach ($students as $student) {
                    $cumulativeSks = 0.0;
                    $cumulativeBnkTotal = 0.0;
                    $studentKrsMap = $krsData->get($student->Student_Id, collect())->keyBy('Term_Year_Id');

                    // Gabungkan Year + Term untuk threshold masuk (Contoh: 2021 + 2 = 20212)
                    $entryThreshold = (int) ($student->Entry_Year_Id . $student->Entry_Term_Id);

                    foreach ($allSemesters as $semester) {
                        $currentTerm = (int) $semester->Term_Year_Id;

                        // Hanya proses semester yang >= semester masuk mahasiswa
                        if ($currentTerm < $entryThreshold) {
                            continue;
                        }

                        $krsSem = $studentKrsMap->get($semester->Term_Year_Id);

                        $sks = $krsSem ? (float)$krsSem->sks : 0.0;
                        $bnkTotal = $krsSem ? (float)$krsSem->bnk_total : 0.0;

                        $ips = $sks > 0 ? round($bnkTotal / $sks, 2) : 0.0;

                        $cumulativeSks += $sks;
                        $cumulativeBnkTotal += $bnkTotal;
                        $ipk = $cumulativeSks > 0 ? round($cumulativeBnkTotal / $cumulativeSks, 2) : 0.0;

                        $processedData->push((object)[
                            'Student_Id'    => $student->Student_Id,
                            'Full_Name'     => $student->Full_Name,
                            'Nim'           => $student->Nim,
                            'Entry_Year_Id' => $student->Entry_Year_Id,
                            'Department_Id' => $student->Department_Id,
                            'Term_Year_Id'  => $semester->Term_Year_Id,
                            'sks'           => $sks,
                            'bnk_total'     => $bnkTotal,
                            'ips'           => $ips,
                            'ipk'           => $ipk,
                            'sks_total'     => $cumulativeSks,
                            'status'        => $sks > 0 ? 'Aktif' : 'Cuti',
                        ]);
                    }
                }

                // 4. Filter berdasarkan target semester jika diminta
                if ($targetTermYear) {
                    $processedData = $processedData->where('Term_Year_Id', $targetTermYear);
                }

                // 5. Grouping dan Formatting Akhir
                $grouped = $processedData
                    ->sortByDesc('Term_Year_Id')
                    ->groupBy('Term_Year_Id')
                    ->map(function ($studentsBySemester) {
                        return $studentsBySemester->map(function ($item) {
                            return [
                                'student_id'    => $item->Student_Id,
                                'nama'          => $item->Full_Name,
                                'nim'           => $item->Nim,
                                'entry_year_id' => $item->Entry_Year_Id,
                                'sks'           => $item->sks,
                                'sks_total'     => $item->sks_total,
                                'bnk_total'     => $item->bnk_total,
                                'ips'           => $item->ips,
                                'ipk'           => $item->ipk,
                                'status'        => $item->status,
                                'department_id' => $item->Department_Id,
                            ];
                        })->values();
                    });

                // 6. Pagination
                if ($serverPaging) {
                    $total = $grouped->count();
                    $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
                    $pagedCollection = $grouped->forPage($currentPage, $perPage);

                    $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                        $pagedCollection,
                        $total,
                        $perPage,
                        $currentPage,
                        ['path' => request()->url()]
                    );

                    return $this->successResponse('AKM by semester fetched', $paged, 200, true, $paged);
                }

                return $this->successResponse('AKM by semester fetched', $grouped);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->errorResponse('Validation failed', $e->errors(), 422);
            } catch (\Exception $e) {
                return $this->errorResponse('Something went wrong', $e->getMessage(), 500);
            }
        } else {
            return $this->errorResponse('Something went wrong', 'Page Not Found', 404);
        }
    }

    public function getSksTotalInSemester($studentId, $termYearId)
    {
        return AcdStudentKrs::where('Student_Id', $studentId)
            ->where('Term_Year_Id', '<=', $termYearId)
            ->sum('Sks');
    }
}
