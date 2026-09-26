<?php

namespace App\Http\Controllers;

use App\Models\StudentActivity;
use App\Traits\ApiResponseTrait;
use App\Helpers\CheckJenisToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentActivityControllerPaging extends Controller
{
    use ApiResponseTrait;

    /**
     * GET Student Activity Data
     */
    public function studentActivityData(Request $request)
    {
        try {
            $tokenName  = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            /**
             * ======================================================
             * BASE QUERY (OPTIMIZED)
             * ======================================================
             */
            $query = StudentActivity::query()
                ->select([
                    'Student_Activity_Id',
                    'Program_MBKM',
                    'Jenis_Anggota',
                    'Student_Activity_Type_Id',
                    'Department_Id',
                    'Term_Year_Id',
                    'Judul',
                    'Keterangan',
                    'Lokasi',
                    'No_SK_Tugas',
                    'Tanggal_SK_Tugas',
                    'Tanggal_Mulai',
                    'Tanggal_Selesai',
                ])
                ->with([
                    // master
                    'department:Department_Id,Department_Name',
                    'termYear:Term_Year_Id,Term_Year_Name',
                    'activityType:Student_Activity_Type_Id,Activity_Type_Name',

                    // member
                    'members:Student_Activity_Id,Student_Id,Jenis_Peran',
                    'members.student:Student_Id,Full_Name,Nim',

                    // supervisor
                    'supervisors:Student_Activity_Id,Employee_Id,Pembimbing_Ke,Activity_Supervisor_Category_Id',
                    'supervisors.employee:Employee_Id,Full_Name,Nidn,Nip',
                    'supervisors.category:Activity_Supervisor_Category_Id,Category_Code,Category_Name',
                ]);

            /**
             * ======================================================
             * TOKEN MAHASISWA → hanya aktivitas yg dia ikuti
             * (pakai whereExists → lebih cepat dari whereHas)
             * ======================================================
             */
            if ($tokenName === 'mahasiswa-token') {
                $query->whereExists(function ($q) use ($dataBearer) {
                    $q->selectRaw(1)
                        ->from('acd_student_activity_member as m')
                        ->whereColumn(
                            'm.Student_Activity_Id',
                            'acd_student_activity.Student_Activity_Id'
                        )
                        ->where('m.Student_Id', $dataBearer->Student_Id);
                });
            }

            /**
             * ======================================================
             * FILTERS (AMAN + CEPAT)
             * ======================================================
             */
            $query
                ->when(
                    $request->filled('Department_Id'),
                    fn ($q) => $q->where('Department_Id', $request->Department_Id)
                )
                ->when(
                    $request->filled('Term_Year_Id'),
                    fn ($q) => $q->where('Term_Year_Id', $request->Term_Year_Id)
                )
                ->when(
                    $request->filled('Student_Id'),
                    fn ($q) =>
                        $q->whereExists(function ($sq) use ($request) {
                            $sq->selectRaw(1)
                                ->from('acd_student_activity_member as m2')
                                ->whereColumn(
                                    'm2.Student_Activity_Id',
                                    'acd_student_activity.Student_Activity_Id'
                                )
                                ->where('m2.Student_Id', $request->Student_Id);
                        })
                );

            /**
             * ======================================================
             * PAGINATION (simple → cepat, tanpa COUNT)
             * ======================================================
             */
            $activities = $query->simplePaginate(
                $request->get('per_page', 2000000)
            );
            

            /**
             * ======================================================
             * TRANSFORM RESPONSE
             * ======================================================
             */
            $data = $activities->getCollection()->map(function ($activity) {
                return [
                    'program_mbkm' => $activity->Program_MBKM,
                    'jenis_anggota' => $activity->Jenis_Anggota,

                    'nama_jenis_aktivitas_mahasiswa' =>
                        $activity->activityType->Activity_Type_Name ?? null,

                    'prodi' => $activity->Department_Id,
                    'id_semester' => $activity->Term_Year_Id,

                    'judul' => $activity->Judul,
                    'keterangan' => $activity->Keterangan,
                    'lokasi' => $activity->Lokasi,

                    'sk_tugas' => $activity->No_SK_Tugas,
                    'tanggal_sk_tugas' => $activity->Tanggal_SK_Tugas,
                    'tanggal_mulai' => $activity->Tanggal_Mulai,
                    'tanggal_selesai' => $activity->Tanggal_Selesai,

                    'list_peserta' => $activity->members->map(fn ($m) => [
                        'nama' => $m->student->Full_Name ?? null,
                        'nim'  => $m->student->Nim ?? null,
                        'peran' => $m->Jenis_Peran,
                    ]),

                    'list_dosen_pembimbing' => $activity->supervisors->map(fn ($s) => [
                        'nama_dosen' => $s->employee->Full_Name ?? null,
                        'nidn' => $s->employee->Nidn ?? null,
                        'nip'  => $s->employee->Nip ?? null,
                        'pembimbing_ke' => $s->Pembimbing_Ke,
                        'kategori' => [
                            'kode' => $s->category->Category_Code ?? null,
                            'nama' => $s->category->Category_Name ?? null,
                        ],
                    ]),
                ];
            });

            // inject kembali ke paginator
            $activities->setCollection($data);

            return $this->successResponse('fetched', $activities);

        } catch (Exception $e) {
            Log::error('StudentActivityController Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return $this->errorResponse(
                'failed to fetch student activity',
                $e->getMessage()
            );
        }
    }
}
