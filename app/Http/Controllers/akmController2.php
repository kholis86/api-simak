<?php

namespace App\Http\Controllers;

use App\Helpers\CheckJenisToken;
use App\Models\AcdStudentKrs;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class akmController2 extends Controller
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

            // Query
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

            // Step 2: ambil transcript mahasiswa di page ini
            $studentIds = $studentsCollection->pluck('Student_Id');

            $transcripts = DB::table('acd_transcript')
                ->whereIn('Student_Id', $studentIds)
                ->select('Student_Id', 'Term_Year_Id', DB::raw('SUM(Sks) as sks_semester'), DB::raw('SUM(Bnk_Value) as bnk_total'))
                ->groupBy('Student_Id', 'Term_Year_Id')
                ->orderBy('Term_Year_Id')
                ->get();
            // Optimasi: groupBy Student_Id untuk akses cepat
            $transcriptsByStudent = $transcripts->groupBy('Student_Id');

            // Step 3: mapping AKM per mahasiswa
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
                })->reverse()->values(); // reset key dari 0,1,2...

                return [
                    'nama' => $student->Full_Name,
                    'nim' => $student->Nim,
                    'akm' => $akm
                ];
            });

            // 4. Jika serverPaging true, buat paginator manual dari $studentsData
            if ($serverPaging && $studentsPage instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $studentsDataPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $studentsData,                 // items hasil mapping AKM
                    $studentsPage->total(),        // total mahasiswa dari paginator asli
                    $studentsPage->perPage(),      // perPage dari paginator asli
                    $studentsPage->currentPage(),  // current page dari paginator asli
                    ['path' => request()->url()]   // path untuk link pagination
                );

                return $this->successResponse(
                    'AKM fetched successfully',
                    $studentsDataPaginator,        // tetap instance LengthAwarePaginator
                    200,
                    $serverPaging,
                    $studentsDataPaginator
                );
            }

            // 5. Jika serverPaging false, kirim semua data
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

                // ... (Token logic remains the same) ...
                $tokenName = CheckJenisToken::getName($request);
                $dataBearer = $request->user();

                // --- 1. Mendapatkan Term_Year_Id Semester Aktif ---
                // Mencari Term_Year_Id di mana tanggal hari ini (Carbon::now()) berada di antara Start_Date dan End_Date
                $activeTermYear = DB::table('mstr_term_year')
                    ->where('Start_Date', '<=', Carbon::now())
                    ->where('End_Date', '>=', Carbon::now())
                    ->value('Term_Year_id');
                // --- End: Mendapatkan Term_Year_Id Semester Aktif ---


                // ... (Query Builder logic remains the same) ...
                $query = DB::table('acd_student_krs as k')
                    ->join('acd_student as s', 's.Student_Id', '=', 'k.Student_Id')
                    ->leftJoin('acd_student_khs as h', 'h.Krs_Id', '=', 'k.Krs_Id')
                    ->leftJoin('acd_transcript as t', 't.Khs_Id', '=', 'h.Khs_Id')
                    ->select(
                        's.Student_Id',
                        's.Full_Name',
                        's.Nim',
                        's.Entry_Year_Id',
                        's.Department_Id',
                        'k.Term_Year_Id',
                        DB::raw('SUM(COALESCE(t.Sks, k.Sks)) as sks'),
                        DB::raw('SUM(COALESCE(t.Bnk_Value, 0)) as bnk_total')
                    )
                    ->groupBy(
                        's.Student_Id',
                        's.Full_Name',
                        's.Nim',
                        's.Entry_Year_Id',
                        's.Department_Id',
                        'k.Term_Year_Id'
                    )
                    ->orderBy('k.Term_Year_Id', 'DESC');

                // ... (Filters and Pagination logic remains the same) ...
                if ($tokenName == 'mahasiswa-token') {
                    $query->where('s.Student_Id', $dataBearer->Student_Id);
                }

                $query->when(
                    $request->student_id,
                    fn($q) =>
                    $q->where('s.Student_Id', $request->student_id)
                );
                $query->when(
                    $request->nim,
                    fn($q) =>
                    $q->where('s.Nim', $request->nim)
                );
                $query->when(
                    $request->entry_year_id,
                    fn($q) =>
                    $q->where('s.Entry_Year_Id', $request->entry_year_id)
                );
                $query->when(
                    $request->term_year_id,
                    fn($q) =>
                    $q->where('k.Term_Year_Id', $request->term_year_id)
                );
                $query->when(
                    $request->department_id,
                    fn($q) =>
                    $q->where('s.Department_Id', $request->department_id)
                );

                if ($serverPaging) {
                    $paginator = $query->paginate($perPage);
                    $data = collect($paginator->items());
                } else {
                    $data = $query->get();
                    $paginator = null;
                }

                // --- LOGIKA PERHITUNGAN KUMULATIF BARU (IPS dan IPK) ---
                $processedData = $data
                    // 1. Kelompokkan berdasarkan Mahasiswa
                    ->groupBy('Student_Id')
                    ->map(function ($studentSemesters) use ($activeTermYear) {
                        // Variabel untuk melacak nilai kumulatif dan IPS semester sebelumnya
                        $cumulativeSks = 0.0;
                        $cumulativeBnkTotal = 0.0;
                        $previousIps = 0.0; // Tambahan: Melacak IPS semester yang sudah selesai

                        // 2. Urutkan semester dari TERLAMA ke TERBARU untuk perhitungan kumulatif
                        $studentSemesters = $studentSemesters->sortBy('Term_Year_Id');

                        // 3. Iterasi untuk menghitung IPS dan IPK
                        return $studentSemesters->map(function ($item) use (&$cumulativeSks, &$cumulativeBnkTotal, &$previousIps, $activeTermYear) {

                            $sks = (float) $item->sks;
                            $bnkTotal = (float) $item->bnk_total;
                            $termYearId = (int) $item->Term_Year_Id;

                            // Hitung IPS saat ini (sebelum cek semester aktif)
                            $currentIps = $sks > 0 ? round($bnkTotal / $sks, 2) : 0.0;

                            $finalIps = $currentIps;
                            $finalIpk = 0.0;

                            // Cek apakah ini adalah SEMESTER AKTIF
                            if ($activeTermYear && $termYearId === (int) $activeTermYear) {

                                // JIKA SEMESTER AKTIF:
                                // IPS: Gunakan IPS dari semester yang sudah selesai sebelumnya
                                $finalIps = $previousIps;

                                // IPK: Gunakan IPK kumulatif dari semester yang sudah selesai sebelumnya
                                $finalIpk = $cumulativeSks > 0 ? round($cumulativeBnkTotal / $cumulativeSks, 2) : 0.0;

                                // SKS/BNK Total semester aktif TIDAK dihitung ke kumulatif, 
                                // dan $previousIps tidak diperbarui.

                            } else {
                                // JIKA BUKAN SEMESTER AKTIF (Semester sudah selesai/lampau):

                                // IPS: Gunakan IPS yang baru dihitung
                                $finalIps = $currentIps;

                                // Perbarui total kumulatif
                                $cumulativeSks += $sks;
                                $cumulativeBnkTotal += $bnkTotal;

                                // Hitung IPK (Indeks Prestasi Kumulatif) baru
                                $finalIpk = $cumulativeSks > 0 ? round($cumulativeBnkTotal / $cumulativeSks, 2) : 0.0;

                                // Simpan IPS ini sebagai IPS sebelumnya untuk iterasi berikutnya
                                $previousIps = $currentIps;
                            }

                            // Kembalikan objek dengan field IPS dan IPK
                            return (object) array_merge((array) $item, [
                                'sks'           => $sks,
                                'bnk_total'     => $bnkTotal,
                                'ips'           => $finalIps,
                                'ipk'           => $finalIpk,
                            ]);
                        });
                    })
                    // 4. Ratakan kembali ke koleksi tunggal
                    ->flatten(1)
                    // 5. Urutkan kembali berdasarkan Term_Year_Id DESC (terbaru dulu)
                    ->sortByDesc('Term_Year_Id');
                // -------------------------------------------------------------

                // Group: Semester → Angkatan → Mahasiswa (Menggunakan $processedData)
                // $grouped = $processedData
                //     ->groupBy('Term_Year_Id')
                //     ->sortKeysDesc()
                //     ->map(function ($studentsBySemester) {
                //         return $studentsBySemester
                //             ->groupBy('Entry_Year_Id')
                //             ->sortKeysDesc()
                //             ->map(function ($studentsByYear) {
                //                 return $studentsByYear->map(function ($item) {
                //                     return [
                //                         'student_id'    => $item->Student_Id,
                //                         'nama'          => $item->Full_Name,
                //                         'nim'           => $item->Nim,
                //                         'sks'           => $item->sks,
                //                         'bnk_total'     => $item->bnk_total,
                //                         'ips'           => $item->ips,
                //                         'ipk'           => $item->ipk,
                //                         'department_id' => $item->Department_Id,
                //                     ];
                //                 })->values();
                //             });
                //     });

                // Group: Semester → Mahasiswa (Angkatan dihilangkan)
                $grouped = $processedData
                    ->groupBy('Term_Year_Id')
                    ->sortKeysDesc()
                    ->map(function ($studentsBySemester) {
                        // Langsung kembalikan array list mahasiswa
                        return $studentsBySemester->map(function ($item) {
                            $sksFieldTotal = $this->getSksTotalInSemester($item->Student_Id, $item->Term_Year_Id);
                            return [
                                'student_id'    => $item->Student_Id,
                                'nama'          => $item->Full_Name,
                                'nim'           => $item->Nim,
                                'entry_year_id' => $item->Entry_Year_Id, // Opsional: tetap sertakan data angkatan
                                'sks'           => $item->sks,
                                'sks_total'     => $sksFieldTotal,
                                'bnk_total'     => $item->bnk_total,
                                'ips'           => $item->ips,
                                'ipk'           => $item->ipk,
                                'status'        => 'Aktif',
                                'department_id' => $item->Department_Id,
                            ];
                        })->values();
                    });


                if ($serverPaging) {
                    // Buat paginator baru dengan data yang sudah ter-grouping
                    $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                        $grouped,
                        $paginator->total(),
                        $paginator->perPage(),
                        $paginator->currentPage(),
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

                $tokenName = CheckJenisToken::getName($request);
                $dataBearer = $request->user();

                // Menggunakan acd_student_krs (k) sebagai sumber data utama
                $query = DB::table('acd_student_krs as k')
                    ->join('acd_student as s', 's.Student_Id', '=', 'k.Student_Id')

                    // LEFT JOIN ke acd_student_khs (h) menggunakan Krs_Id
                    ->leftJoin('acd_student_khs as h', 'h.Krs_Id', '=', 'k.Krs_Id')

                    // LEFT JOIN ke acd_transcript (t) menggunakan Khs_Id
                    ->leftJoin('acd_transcript as t', 't.Khs_Id', '=', 'h.Khs_Id')

                    ->select(
                        's.Student_Id',
                        's.Full_Name',
                        's.Nim',
                        's.Entry_Year_Id',
                        's.Department_Id',
                        'k.Term_Year_Id', // Ambil Term_Year_Id dari krs

                        // SKS: Gunakan SKS dari transcript (jika ada nilai), atau SKS dari krs (jika belum ada)
                        DB::raw('SUM(COALESCE(t.Sks, k.Sks)) as sks'),

                        // BNK Total: Gunakan Bnk_Value dari transcript (jika ada nilai), atau 0 (jika belum ada)
                        DB::raw('SUM(COALESCE(t.Bnk_Value, 0)) as bnk_total')
                    )
                    ->groupBy(
                        's.Student_Id',
                        's.Full_Name',
                        's.Nim',
                        's.Entry_Year_Id',
                        's.Department_Id',
                        'k.Term_Year_Id' // Grouping per semester
                    )
                    ->orderBy('k.Term_Year_Id', 'DESC'); // Tetap urutkan DESC untuk hasil awal

                if ($tokenName == 'mahasiswa-token') {
                    $query->where('s.Student_Id', $dataBearer->Student_Id);
                }

                $query->when(
                    $request->student_id,
                    fn($q) =>
                    $q->where('s.Student_Id', $request->student_id)
                );
                $query->when(
                    $request->nim,
                    fn($q) =>
                    $q->where('s.Nim', $request->nim)
                );
                $query->when(
                    $request->entry_year_id,
                    fn($q) =>
                    $q->where('s.Entry_Year_Id', $request->entry_year_id)
                );
                $query->when(
                    $request->term_year_id,
                    fn($q) =>
                    $q->where('k.Term_Year_Id', $request->term_year_id)
                );
                $query->when(
                    $request->department_id,
                    fn($q) =>
                    $q->where('s.Department_Id', $request->department_id)
                );

                if ($serverPaging) {
                    $paginator = $query->paginate($perPage);
                    $data = collect($paginator->items());
                } else {
                    $data = $query->get();
                    $paginator = null;
                }

                // --- LOGIKA PERHITUNGAN KUMULATIF BARU (IPS dan IPK) ---
                $processedData = $data
                    // 1. Kelompokkan berdasarkan Mahasiswa
                    ->groupBy('Student_Id')
                    ->map(function ($studentSemesters) {

                        $cumulativeSks = 0;
                        $cumulativeBnkTotal = 0;

                        // 2. Urutkan semester dari TERLAMA ke TERBARU untuk perhitungan kumulatif
                        $studentSemesters = $studentSemesters->sortBy('Term_Year_Id');

                        // 3. Iterasi untuk menghitung IPS dan IPK
                        return $studentSemesters->map(function ($item) use (&$cumulativeSks, &$cumulativeBnkTotal) {

                            $sks = (float) $item->sks;
                            $bnkTotal = (float) $item->bnk_total;

                            // Hitung IPS (Indeks Prestasi Semester)
                            $ips = $sks > 0 ? round($bnkTotal / $sks, 2) : 0;

                            // Perbarui total kumulatif
                            $cumulativeSks += $sks;
                            $cumulativeBnkTotal += $bnkTotal;

                            // Hitung IPK (Indeks Prestasi Kumulatif)
                            $ipk = $cumulativeSks > 0 ? round($cumulativeBnkTotal / $cumulativeSks, 2) : 0;

                            // Kembalikan objek dengan field IPS dan IPK
                            return (object) array_merge((array) $item, [
                                'sks'           => $sks,
                                'bnk_total'     => $bnkTotal,
                                'ips'           => $ips,
                                'ipk'           => $ipk,
                                'status'        => 'Aktif',
                                'sks_total'     => $cumulativeSks,
                            ]);
                        });
                    })
                    // 4. Ratakan kembali ke koleksi tunggal
                    ->flatten(1)
                    // 5. Urutkan kembali berdasarkan Term_Year_Id DESC (terbaru dulu)
                    ->sortByDesc('Term_Year_Id');
                // -------------------------------------------------------------

                // Group: Semester → Angkatan → Mahasiswa (Menggunakan $processedData)
                // $grouped = $processedData
                //     ->groupBy('Term_Year_Id')
                //     ->sortKeysDesc()
                //     ->map(function ($studentsBySemester) {
                //         return $studentsBySemester
                //             ->groupBy('Entry_Year_Id')
                //             ->sortKeysDesc()
                //             ->map(function ($studentsByYear) {
                //                 return $studentsByYear->map(function ($item) {
                //                     return [
                //                         'student_id'    => $item->Student_Id,
                //                         'nama'          => $item->Full_Name,
                //                         'nim'           => $item->Nim,
                //                         'sks'           => $item->sks,
                //                         'bnk_total'     => $item->bnk_total,
                //                         'ips'           => $item->ips, // Ganti dari 'ipk' menjadi 'ips'
                //                         'ipk'           => $item->ipk, // Field IPK baru (Kumulatif)
                //                         'department_id' => $item->Department_Id,
                //                     ];
                //                 })->values();
                //             });
                //     });

                // Group: Semester → Mahasiswa (Angkatan dihilangkan)
                $grouped = $processedData
                    ->groupBy('Term_Year_Id')
                    ->sortKeysDesc()
                    ->map(function ($studentsBySemester) {
                        return $studentsBySemester->map(function ($item) {

                            $sksFieldTotal = $this->getSksTotalInSemester($item->Student_Id, $item->Term_Year_Id);
                            return [
                                'student_id'    => $item->Student_Id,
                                'nama'          => $item->Full_Name,
                                'nim'           => $item->Nim,
                                'sks'           => $item->sks,
                                'sks_total'     => $sksFieldTotal, // Field sks_total baru (Kumulatif)
                                'bnk_total'     => $item->bnk_total,
                                'ips'           => $item->ips, // Ganti dari 'ipk' menjadi 'ips'
                                'ipk'           => $item->ipk, // Field IPK baru (Kumulatif)
                                'department_id' => $item->Department_Id,
                            ];
                        })->values();
                    });

                if ($serverPaging) {
                    // Buat paginator baru dengan data yang sudah ter-grouping
                    $paged = new \Illuminate\Pagination\LengthAwarePaginator(
                        $grouped,
                        $paginator->total(),
                        $paginator->perPage(),
                        $paginator->currentPage(),
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

    // Asumsi class Anda sudah mengimplementasikan trait atau method successResponse/errorResponse

    public function getSksTotalInSemester($studentId, $termYearId)
    {
        $sksTotal = AcdStudentKrs::where('Student_Id', $studentId)
            ->where('Term_Year_Id', '<=', $termYearId)
            ->sum('Sks'); // <-- Ini yang diubah

        return $sksTotal;
    }
}
