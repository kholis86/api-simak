<?php

namespace App\Http\Controllers;

use App\Models\AcdStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Helpers\TanggalIndo;

class StudentController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/students",
     *     tags={"Students"},
     *     summary="Ambil data mahasiswa",
     *     description="Endpoint ini digunakan untuk mengambil data mahasiswa. Dapat menampilkan versi ringkas atau detail, dengan atau tanpa pagination.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         required=false,
     *         description="ID mahasiswa (filter spesifik)",
     *         @OA\Schema(type="integer", example=1234)
     *     ),
     *     @OA\Parameter(
     *         name="nim",
     *         in="query",
     *         required=false,
     *         description="Nomor Induk Mahasiswa (filter spesifik)",
     *         @OA\Schema(type="integer", example=24001123)
     *     ),
     *     @OA\Parameter(
     *         name="register_number",
     *         in="query",
     *         required=false,
     *         description="Nomor registrasi (filter spesifik)",
     *         @OA\Schema(type="integer", example=102030)
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=false,
     *         description="ID jurusan (filter spesifik)",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="entry_year",
     *         in="query",
     *         required=false,
     *         description="Tahun masuk mahasiswa",
     *         @OA\Schema(type="integer", example=2023)
     *     ),
     *     @OA\Parameter(
     *         name="detail",
     *         in="query",
     *         required=false,
     *         description="Tampilkan data detail mahasiswa (true/false)",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="server_paging",
     *         in="query",
     *         required=false,
     *         description="Aktifkan server-side pagination (true/false)",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (jika server_paging=true)",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Data mahasiswa berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Student fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     oneOf={
     *                         @OA\Schema(
     *                             title="Versi Ringkas",
     *                             @OA\Property(property="Student_Id", type="integer", example=1),
     *                             @OA\Property(property="Nim", type="string", example="24001123"),
     *                             @OA\Property(property="Full_Name", type="string", example="Ahmad Setiawan"),
     *                             @OA\Property(property="Department", type="string", example="Teknik Informatika"),
     *                             @OA\Property(property="Class_Program", type="string", example="Reguler"),
     *                             @OA\Property(property="Religion", type="string", example="Islam"),
     *                             @OA\Property(property="Gender_Type", type="string", example="Laki-laki"),
     *                             @OA\Property(property="Birth_Place", type="string", example="Bandung"),
     *                             @OA\Property(property="Birth_Date", type="string", format="date", example="2004-02-01")
     *                         ),
     *                         @OA\Schema(
     *                             title="Versi Detail",
     *                             @OA\Property(property="Student_Id", type="integer", example=1),
     *                             @OA\Property(property="NIM", type="string", example="24001123"),
     *                             @OA\Property(property="Nama", type="string", example="Ahmad Setiawan"),
     *                             @OA\Property(property="Tempat_Lahir", type="string", example="Bandung"),
     *                             @OA\Property(property="Tanggal_Lahir", type="string", example="1 Februari 2004"),
     *                             @OA\Property(property="Jenis_Kelamin", type="string", example="Laki-laki"),
     *                             @OA\Property(property="NIK", type="string", example="3201234567890001"),
     *                             @OA\Property(property="Agama", type="string", example="Islam"),
     *                             @OA\Property(property="NISN", type="string", example="9999999999"),
     *                             @OA\Property(property="Jalur_Pendaftaran", type="string", example="SNBP"),
     *                             @OA\Property(property="NPWP", type="string", example="-"),
     *                             @OA\Property(property="Kewarganegaraan", type="string", example="Indonesia"),
     *                             @OA\Property(property="Jenis_Pendaftaran", type="string", example="Baru"),
     *                             @OA\Property(property="Tgl_Masuk_Kuliah", type="string", example="10 Agustus 2023"),
     *                             @OA\Property(property="Alamat", type="string", example="Jl. Soekarno-Hatta No. 10"),
     *                             @OA\Property(property="Kelurahan", type="string", example="Sukapura"),
     *                             @OA\Property(property="Kecamatan", type="string", example="Cibiru"),
     *                             @OA\Property(property="Kode_Pos", type="string", example="40293"),
     *                             @OA\Property(property="Transportasi", type="string", example="Motor"),
     *                             @OA\Property(property="No_HP", type="string", example="08123456789"),
     *                             @OA\Property(property="Email", type="string", example="ahmad@student.univ.ac.id"),
     *                             @OA\Property(property="Terima_KPS", type="string", example="Tidak"),
     *                             @OA\Property(property="No_KPS", type="string", example="-"),
     *                             @OA\Property(property="Ayah_Name", type="string", example="Budi Setiawan"),
     *                             @OA\Property(property="Ayah_Education_Type_Name", type="string", example="S1"),
     *                             @OA\Property(property="Ayah_Job_Category_Name", type="string", example="Wiraswasta"),
     *                             @OA\Property(property="Ayah_Income", type="number", example=7000000),
     *                             @OA\Property(property="Ibu_Name", type="string", example="Siti Aminah"),
     *                             @OA\Property(property="Ibu_Education_Type_Name", type="string", example="SMA"),
     *                             @OA\Property(property="Ibu_Job_Category_Name", type="string", example="Ibu Rumah Tangga"),
     *                             @OA\Property(property="Ibu_Income", type="number", example=0),
     *                             @OA\Property(property="Wali_Name", type="string", example="-"),
     *                             @OA\Property(property="Wali_Education_Type_Name", type="string", example="-"),
     *                             @OA\Property(property="Wali_Job_Category_Name", type="string", example="-"),
     *                             @OA\Property(property="Wali_Income", type="string", example="-"),
     *                             @OA\Property(property="Jumlah_Biaya_Masuk", type="number", example=7500000),
     *                             @OA\Property(property="Jenis_Pembiayaan", type="string", example="-"),
     *                             @OA\Property(property="SKS_Diakui", type="string", example="-"),
     *                             @OA\Property(property="Asal_Perguruan_Tinggi", type="string", example="-"),
     *                             @OA\Property(property="Asal_Program_Studi", type="string", example="-")
     *                         )
     *                     }
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=120)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="data", type="object",
     *                 example={"nim": {"The nim must be an integer."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi kesalahan server internal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function studentData(Request $request)
    {
        try {
            // validasi input
            $request->validate([
                'nim' => 'nullable|integer',
                'register_number' => 'nullable|integer',
                'department_id' => 'nullable|integer',
            ]);

            $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);
            $perPage = (int) $request->get('per_page', 20);
            $detail = $request->boolean('detail', false);

            $students = [];

            $query = DB::table('acd_student as s')
                ->leftJoin('mstr_department as d', 's.Department_Id', '=', 'd.Department_Id')
                ->leftJoin('mstr_class_program as cp', 's.Class_Prog_Id', '=', 'cp.Class_Prog_Id')
                ->leftJoin('mstr_gender as g', 's.Gender_Id', '=', 'g.Gender_Id')
                ->leftJoin('mstr_religion as r', 's.Religion_Id', '=', 'r.Religion_Id');

            // === SELECT KONDISIONAL BERDASARKAN DETAIL ===
            if ($detail) {
                $query
                    ->leftJoin('mstr_register_status as rs', 's.Register_Status_Id', '=', 'rs.Register_Status_Id')
                    ->leftJoin('mstr_citizenship as ctz', 's.Citizenship_Id', '=', 'ctz.Citizenship_Id')
                    ->leftJoin('mstr_transport_type as t', 's.Transport_Type_Id', '=', 't.Transport_Type_Id')
                    ->leftJoin('acd_student_address as addr', 's.Student_Id', '=', 'addr.Student_Id')
                    ->leftJoin('mstr_district as dist', 'addr.District_Id', '=', 'dist.District_Id')
                    ->select(
                        's.Student_Id',
                        's.Nim',
                        's.Full_Name',
                        's.Register_Number',
                        's.Entry_Year_Id',
                        's.Entry_Term_Id',
                        's.Nisn',
                        's.Nik',
                        's.Npwp',
                        's.Phone_Mobile',
                        's.Email_Corporate',
                        's.Birth_Place',
                        's.Birth_Date',
                        's.Recieve_Kps',
                        's.Kps_Number',
                        'd.Department_Name',
                        'cp.Class_Program_Name',
                        'g.Gender_Type',
                        'r.Religion_Name',
                        'rs.Register_Status_Name',
                        'ctz.Citizenship_Name',
                        't.Transport_Type',
                        'addr.Address',
                        'addr.Rt',
                        'addr.Rw',
                        'addr.Dusun',
                        'addr.Sub_District',
                        'dist.District_Name',
                        'addr.Zip_Code'
                    );
            } else {
                $query->select(
                    's.Student_Id',
                    's.Nim',
                    's.Register_Number',
                    's.Full_Name',
                    'd.Department_Name as Department',
                    'cp.Class_Program_Name as Class_Program',
                    'r.Religion_Name as Religion',
                    's.Birth_Place',
                    's.Birth_Date',
                    's.Entry_Year_Id',
                    's.Entry_Term_Id',
                    's.Nisn',
                    's.Nik',
                    's.Email_Corporate',
                    's.Phone_Mobile',
                    'g.Gender_Type'
                );
            }

            // === FILTER ===
            if ($request->filled('student_id')) $query->where('s.Student_Id', $request->student_id);
            if ($request->filled('nim')) $query->where('s.Nim', $request->nim);
            if ($request->filled('register_number')) $query->where('s.Register_Number', $request->register_number);
            if ($request->filled('department_id')) $query->where('s.Department_Id', $request->department_id);
            if ($request->filled('entry_year')) $query->where('s.Entry_Year_Id', $request->entry_year);

            $query->orderBy('s.Nim');

            // === DETAIL DATA PREFETCH ===
            if ($detail) {
                $termYears = DB::table('mstr_term_year')->get()->keyBy('Term_Year_Id');
                $allParents = DB::table('acd_student_parent as p')
                    ->leftJoin('mstr_education_type as edu', 'p.Education_Type_Id', '=', 'edu.Education_Type_Id')
                    ->leftJoin('mstr_job_category as job', 'p.Job_Category_Id', '=', 'job.Job_Category_Id')
                    ->select(
                        'p.Student_Id',
                        'p.Parent_Type_Id',
                        'p.Nik',
                        'p.Full_Name',
                        'p.Birth_Date',
                        'edu.Education_Type_Name',
                        'job.Job_Category_Name',
                        'p.Income'
                    )
                    ->get()
                    ->groupBy('Student_Id');

                $allCamaru = DB::table('reg_camaru as ca')
                    ->join('reg_register_type as rt', 'ca.Register_Type_Id', '=', 'rt.Register_Type_Id')
                    ->select('ca.Reg_Num', 'rt.Register_Type_Name')
                    ->get()
                    ->pluck('Register_Type_Name', 'Reg_Num');

                $allPayments = DB::table('fnc_student_payment as pay')
                    ->join('fnc_reff_payment as reff', 'pay.Reff_Payment_Id', '=', 'reff.Reff_Payment_Id')
                    ->where('pay.Is_Her', 1)
                    ->select('reff.Register_Number', 'reff.Total_Amount')
                    ->get()
                    ->pluck('Total_Amount', 'Register_Number');
            }

            if ($serverPaging) {
                // === PAGING ===
                $paginated = $query->paginate($perPage);
                $termYears   = $termYears ?? [];
                $allParents  = $allParents ?? [];
                $allCamaru   = $allCamaru ?? [];
                $allPayments = $allPayments ?? [];
                $students = $paginated->through(function ($s) use ($detail, $termYears, $allParents, $allCamaru, $allPayments) {
                    $birth = $s->Birth_Date
                        ? TanggalIndo::tanggal(date('Y-m-d', strtotime($s->Birth_Date)), true)
                        : null;
                    if ($detail) {
                        $termId = $s->Entry_Year_Id . $s->Entry_Term_Id;
                        $masukKuliah = '';
                        if (isset($termYears[$termId])) {
                            $tgl = date('Y-m-d', strtotime($termYears[$termId]->Start_Date));
                            $masukKuliah = TanggalIndo::tanggal($tgl, false);
                        }

                        $parents = $allParents[$s->Student_Id] ?? collect();
                        $ayah = $parents->firstWhere('Parent_Type_Id', 1);
                        $ibu  = $parents->firstWhere('Parent_Type_Id', 2);
                        $wali = $parents->firstWhere('Parent_Type_Id', 3);

                        return [
                            'Student_Id' => $s->Student_Id,
                            'NIM' => $s->Nim,
                            'Nama' => $s->Full_Name,
                            'Tempat_Lahir' => $s->Birth_Place,
                            'Tanggal_Lahir' => $birth,
                            'Jenis_Kelamin' => $s->Gender_Type,
                            'NIK' => $s->Nik,
                            'Agama' => $s->Religion_Name,
                            'NISN' => $s->Nisn,
                            'Jalur_Pendaftaran' => $allCamaru[$s->Register_Number] ?? '',
                            'NPWP' => $s->Npwp,
                            'Kewarganegaraan' => $s->Citizenship_Name,
                            'Jenis_Pendaftaran' => $s->Register_Status_Name,
                            'Tgl_Masuk_Kuliah' => $masukKuliah,
                            'Alamat' => $s->Address,
                            'Kelurahan' => $s->Sub_District,
                            'Kecamatan' => $s->District_Name,
                            'Kode Pos' => $s->Zip_Code,
                            'Transportasi' => $s->Transport_Type,
                            'No_HP' => $s->Phone_Mobile,
                            'Email' => $s->Email_Corporate,
                            'Terima_KPS' => $s->Recieve_Kps ? 'Ya' : 'Tidak',
                            'No_KPS' => $s->Kps_Number,
                            'Ayah_Name' => $ayah?->Full_Name ?? '',
                            'Ayah_Education_Type_Name' => $ayah?->Education_Type_Name ?? '',
                            'Ayah_Job_Category_Name' => $ayah?->Job_Category_Name ?? '',
                            'Ayah_Income' => $ayah?->Income ?? '',
                            'Ibu_Name' => $ibu?->Full_Name ?? '',
                            'Ibu_Education_Type_Name' => $ibu?->Education_Type_Name ?? '',
                            'Ibu_Job_Category_Name' => $ibu?->Job_Category_Name ?? '',
                            'Ibu_Income' => $ibu?->Income ?? '',
                            'Wali_Name' => $wali?->Full_Name ?? '',
                            'Wali_Education_Type_Name' => $wali?->Education_Type_Name ?? '',
                            'Wali_Job_Category_Name' => $wali?->Job_Category_Name ?? '',
                            'Wali_Income' => $wali?->Income ?? '',
                            'Jumlah_Biaya Masuk' => $allPayments[$s->Register_Number] ?? '',
                            'Jenis Pembiayaan' => '',
                            'SKS Diakui' => '',
                            'Asal Perguruan Tinggi' => '',
                            'Asal Program Studi' => '',
                        ];
                    } else {
                        return (array) $s;
                    }
                });
                return $this->successResponse('Student fetched successfully', $students, 200, $serverPaging, $students);
            } else {
                // === NON PAGING ===
                foreach ($query->cursor() as $s) {
                    $birth = TanggalIndo::tanggal(date('Y-m-d', strtotime($s->Birth_Date)), true);

                    if ($detail) {
                        $termId = $s->Entry_Year_Id . $s->Entry_Term_Id;
                        $masukKuliah = '';
                        if (isset($termYears[$termId])) {
                            $tgl = date('Y-m-d', strtotime($termYears[$termId]->Start_Date));
                            $masukKuliah = TanggalIndo::tanggal($tgl, false);
                        }

                        $parents = $allParents[$s->Student_Id] ?? collect();
                        $ayah = $parents->firstWhere('Parent_Type_Id', 1);
                        $ibu  = $parents->firstWhere('Parent_Type_Id', 2);
                        $wali = $parents->firstWhere('Parent_Type_Id', 3);

                        $students[] = [
                            'Student_Id' => $s->Student_Id,
                            'NIM' => $s->Nim,
                            'Nama' => $s->Full_Name,
                            'Tempat Lahir' => $s->Birth_Place,
                            'Tanggal Lahir' => $birth,
                            'Jenis Kelamin' => $s->Gender_Type,
                            'NIK' => $s->Nik,
                            'Agama' => $s->Religion_Name,
                            'NISN' => $s->Nisn,
                            'Jalur Pendaftaran' => $allCamaru[$s->Register_Number] ?? '',
                            'NPWP' => $s->Npwp,
                            'Kewarganegaraan' => $s->Citizenship_Name,
                            'Jenis Pendaftaran' => $s->Register_Status_Name,
                            'Tgl Masuk Kuliah' => $masukKuliah,
                            'Alamat' => $s->Address,
                            'Kelurahan' => $s->Sub_District,
                            'Kecamatan' => $s->District_Name,
                            'Kode Pos' => $s->Zip_Code,
                            'Transportasi' => $s->Transport_Type,
                            'No HP' => $s->Phone_Mobile,
                            'Email' => $s->Email_Corporate,
                            'Terima KPS' => $s->Recieve_Kps ? 'Ya' : 'Tidak',
                            'No KPS' => $s->Kps_Number,
                            'Ayah_Name' => $ayah?->Full_Name ?? '',
                            'Ayah_Education_Type_Name' => $ayah?->Education_Type_Name ?? '',
                            'Ayah_Job_Category_Name' => $ayah?->Job_Category_Name ?? '',
                            'Ayah_Income' => $ayah?->Income ?? '',
                            'Ibu_Name' => $ibu?->Full_Name ?? '',
                            'Ibu_Education_Type_Name' => $ibu?->Education_Type_Name ?? '',
                            'Ibu_Job_Category_Name' => $ibu?->Job_Category_Name ?? '',
                            'Ibu_Income' => $ibu?->Income ?? '',
                            'Wali_Name' => $wali?->Full_Name ?? '',
                            'Wali_Education_Type_Name' => $wali?->Education_Type_Name ?? '',
                            'Wali_Job_Category_Name' => $wali?->Job_Category_Name ?? '',
                            'Wali_Income' => $wali?->Income ?? '',
                            'Jumlah Biaya Masuk' => $allPayments[$s->Register_Number] ?? '',
                            'Jenis Pembiayaan' => '',
                            'SKS Diakui' => '',
                            'Asal Perguruan Tinggi' => '',
                            'Asal Program Studi' => '',
                        ];
                    } else {
                        $students[] = (array) $s;
                    }
                }

                return $this->successResponse('Student fetched successfully', $students, 200, $serverPaging);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
