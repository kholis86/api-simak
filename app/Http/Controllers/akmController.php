<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class akmController extends Controller
{
    use ApiResponseTrait;
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

            // Query
            $studentsPage = DB::table('acd_student as s')
                ->when($request->filled('student_id'), fn($q) => $q->where('s.Student_Id', $request->student_id))
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
}
