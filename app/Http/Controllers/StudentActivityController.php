<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentActivity;
use App\Traits\ApiResponseTrait;
use App\Helpers\CheckJenisToken;
use Exception;

class StudentActivityController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/api/student-activity",
     *     tags={"Academic"},
     *     summary="Ambil data aktivitas mahasiswa",
     *     description="Endpoint ini digunakan untuk mengambil data aktivitas mahasiswa. Data akan difilter berdasarkan Student_Id jika menggunakan token mahasiswa.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="Department_Id",
     *         in="query",
     *         required=false,
     *         description="ID Departemen (Prodi)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="Term_Year_Id",
     *         in="query",
     *         required=false,
     *         description="ID Semester (Tahun Ajaran)",
     *         @OA\Schema(type="integer", example=20231)
     *     ),
     *     @OA\Parameter(
     *         name="Student_Id",
     *         in="query",
     *         required=false,
     *         description="ID Mahasiswa (Filter spesifik)",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Data aktivitas mahasiswa berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="fetched"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="program_mbkm", type="string", example="Magang"),
     *                     @OA\Property(property="jenis_anggota", type="string", example="Personal"),
     *                     @OA\Property(property="nama_jenis_aktivitas_mahasiswa", type="string", example="Aktivitas Mahasiswa"),
     *                     @OA\Property(property="prodi", type="integer", example=1),
     *                     @OA\Property(property="id_semester", type="integer", example=20231),
     *                     @OA\Property(property="judul", type="string", example="Judul Aktivitas"),
     *                     @OA\Property(property="keterangan", type="string", example="Keterangan Aktivitas"),
     *                     @OA\Property(property="lokasi", type="string", example="Lokasi Aktivitas"),
     *                     @OA\Property(property="sk_tugas", type="string", example="SK/123/2023"),
     *                     @OA\Property(property="tanggal_sk_tugas", type="string", format="date", example="2023-01-01"),
     *                     @OA\Property(property="tanggal_mulai", type="string", format="date", example="2023-01-01"),
     *                     @OA\Property(property="tanggal_selesai", type="string", format="date", example="2023-06-01"),
     *                     @OA\Property(
     *                         property="list_peserta",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="nama", type="string", example="Ahmad"),
     *                             @OA\Property(property="nim", type="string", example="24001123"),
     *                             @OA\Property(property="jenis_peran", type="string", example="Ketua")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="list_dosen_pembimbing",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="nama_dosen", type="string", example="Dr. Budi"),
     *                             @OA\Property(property="pembimbing_ke", type="integer", example=1),
     *                             @OA\Property(property="nama_kategori_kegiatan", type="string", example="Pembimbing Utama"),
     *                             @OA\Property(property="nidn", type="string", example="0123456789"),
     *                             @OA\Property(property="nuptk", type="string", example="9876543210")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi kesalahan server internal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="failed to fetch")
     *         )
     *     )
     * )
     */
    public function studentActivityData(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $dataBearer = $request->user();

            $query = StudentActivity::query()
                ->with([
                    'department',
                    'termYear',
                    'members.student',
                    'supervisors.employee'
                ]);

            if ($tokenName == 'mahasiswa-token') {
                $query->whereHas('members', function ($m) use ($dataBearer) {
                    $m->where('Student_Id', $dataBearer->Student_Id);
                });
            }

            $query
            ->when($request->filled('Department_Id'), function ($q) use ($request) {
                $q->where('Department_Id', $request->Department_Id);
            })

            ->when($request->filled('Term_Year_Id'), function ($q) use ($request) {
                $q->where('Term_Year_Id', $request->Term_Year_Id);
            })

            ->when($request->filled('Student_Id'), function ($q) use ($request) {
                $q->whereHas('members', function ($m) use ($request) {
                    $m->where('Student_Id', $request->Student_Id);
                });
            });

            $activities = $query->get();

            // 🔄 Transform ke format response yang kamu mau
            $data = $activities->map(function ($activity) {
                return [
                        'program_mbkm' => $activity->Program_MBKM,
                        'jenis_anggota' => $activity->Jenis_Anggota,
                        'nama_jenis_aktivitas_mahasiswa' => $activity->Jenis_Aktivitas,
                        'prodi' => $activity->Department_Id,
                        'id_semester' => $activity->Term_Year_Id,
                        'judul' => $activity->Judul,
                        'keterangan' => $activity->Keterangan,
                        'lokasi' => $activity->Lokasi,
                        'sk_tugas' => $activity->No_SK_Tugas,
                        'tanggal_sk_tugas' => $activity->Tanggal_SK_Tugas,
                        'tanggal_mulai' => $activity->Tanggal_Mulai,
                        'tanggal_selesai' => $activity->Tanggal_Selesai,

                        'list_peserta' => $activity->members->map(function ($member) {
                            return [
                                'nama' => $member->student->Student_Name ?? null,
                                'nim' => $member->student->Student_Number ?? null,
                                'jenis_peran' => $member->Jenis_Peran,
                            ];
                        }),

                        'list_dosen_pembimbing' => $activity->supervisors->map(function ($sup) {
                            return [
                                'nama_dosen' => $sup->employee->Employee_Name ?? null,
                                'pembimbing_ke' => $sup->Pembimbing_Ke,
                                'nama_kategori_kegiatan' => $sup->Kategori_Kegiatan,
                                'nidn' => $sup->employee->NIDN ?? null,
                                'nuptk' => $sup->employee->NUPTK ?? null,
                            ];
                        })
                ];
            });

            return $this->successResponse(
                'fetched',
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'failed to fetch',
                $e->getMessage()
            );
        }
    }
}
