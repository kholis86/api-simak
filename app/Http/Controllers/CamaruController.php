<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Helpers\TanggalIndo;
use App\Helpers\CheckJenisToken;

class CamaruController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/camaru",
     *     tags={"Camaru"},
     *     summary="Get prospective students list (Camaru)",
     *     description="Endpoint untuk mengambil data calon mahasiswa baru secara lengkap dengan informasi periode masuk dan semester.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="prodi_1", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="prodi_2", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="reg_num", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="phone", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="camaru_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="server_paging", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        try {
            $serverPaging = filter_var($request->server_paging, FILTER_VALIDATE_BOOLEAN);
            $perPage = (int) $request->get('per_page', 20);

            $query = DB::table('simak_mou.reg_camaru as ca')
                ->leftJoin('simak_mou.reg_camaru_address as addr', 'ca.Camaru_Id', '=', 'addr.Camaru_Id')
                ->leftJoin('mstr_department as d1', 'ca.Department_Choice_1', '=', 'd1.Department_Id')
                ->leftJoin('mstr_department as d2', 'ca.Department_Choice_2', '=', 'd2.Department_Id')
                ->leftJoin('mstr_class_program as cp1', 'ca.Class_Prog_Choice_1', '=', 'cp1.Class_Prog_Id')
                ->leftJoin('mstr_class_program as cp2', 'ca.Class_Prog_Choice_2', '=', 'cp2.Class_Prog_Id')
                ->leftJoin('mstr_gender as g', 'ca.Gender_Id', '=', 'g.Gender_Id')
                ->leftJoin('simak_mou.mstr_payment_type as pt', 'ca.Payment_Type', '=', 'pt.Payment_Type_Id')
                ->leftJoin('simak_mou.reg_entry_period_sched as eps', 'ca.Entry_Period_Sched_Id', '=', 'eps.Entry_Period_Sched_Id')
                ->leftJoin('simak_mou.mstr_entry_period_type as ept', 'eps.Entry_Period_Type_Id', '=', 'ept.Entry_Period_Type_Id')
                ->leftJoin('mstr_term_year as ty', 'eps.Term_Year_Id', '=', 'ty.Term_Year_Id')
                ->leftJoin('fnc_reff_payment as rp', 'ca.Reff_Payment', '=', 'rp.Reff_Payment_Id')
                ->select(
                    'ca.Camaru_Id',
                    'ca.Reg_Num',
                    'ca.Full_Name',
                    'ca.Birth_Place',
                    'ca.Birth_Date',
                    'ca.Phone_Mobile',
                    'ca.Email_General',
                    'ca.Address',
                    'ca.Zip_Code',
                    'ca.High_School_Origin',
                    'ca.Registration_Date',
                    'ca.Payment_Due_Date as Payment_Date',
                    DB::raw("CASE WHEN ca.Her_Status = 1 THEN 'Paid' ELSE 'Unpaid' END as Payment_Status"),
                    'pt.Description as Payment_Type',
                    'd1.Department_Name as Department_1',
                    'd2.Department_Name as Department_2',
                    'cp1.Class_Program_Name as Class_Program_1',
                    'cp2.Class_Program_Name as Class_Program_2',
                    'g.Gender_Type',
                    'addr.Address as Detail_Address',
                    'addr.Postal_Code',
                    'addr.Phone_Home',
                    'ept.Entry_Period_Type_Name as Entry_Period',
                    'ty.Term_Year_Name as Term'
                );

            // === FILTER ===
            if ($request->filled('camaru_id')) $query->where('ca.Camaru_Id', $request->camaru_id);
            if ($request->filled('prodi_1')) $query->where('ca.Department_Choice_1', $request->prodi_1);
            if ($request->filled('prodi_2')) $query->where('ca.Department_Choice_2', $request->prodi_2);
            if ($request->filled('reg_num')) $query->where('ca.Reg_Num', 'like', '%' . $request->reg_num . '%');
            if ($request->filled('name')) $query->where('ca.Full_Name', 'like', '%' . $request->name . '%');
            if ($request->filled('phone')) $query->where('ca.Phone_Mobile', 'like', '%' . $request->phone . '%');
            if ($request->filled('email')) $query->where('ca.Email_General', 'like', '%' . $request->email . '%');

            $query->orderBy('ca.Full_Name');

            if ($serverPaging) {
                $paginated = $query->paginate($perPage);
                $data = $paginated->through(function ($item) {
                    return $this->formatItem($item);
                });
                return $this->successResponse('Camaru fetched successfully', $data, 200, $serverPaging, $data);
            } else {
                $rawResults = $query->get();
                $data = $rawResults->map(function ($item) {
                    return $this->formatItem($item);
                });
                return $this->successResponse('Camaru fetched successfully', $data, 200, $serverPaging);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * Formatting item (English Keys)
     */
    private function formatItem($s)
    {
        $birthDateIndo = $s->Birth_Date
            ? TanggalIndo::tanggal(date('Y-m-d', strtotime($s->Birth_Date)), true)
            : null;
        
        $paymentDateIndo = $s->Payment_Date
            ? TanggalIndo::tanggal(date('Y-m-d', strtotime($s->Payment_Date)), false)
            : null;

        return [
            'Camaru_Id' => $s->Camaru_Id,
            'Register_Number' => $s->Reg_Num,
            'Full_Name' => $s->Full_Name,
            'Birth_Place' => $s->Birth_Place,
            'Birth_Date' => $birthDateIndo,
            'Gender' => $s->Gender_Type,
            'Email' => $s->Email_General,
            'Phone_Mobile' => $s->Phone_Mobile,
            'Address' => $s->Address,
            'Zip_Code' => $s->Zip_Code,
            'High_School_Origin' => $s->High_School_Origin,
            'Department_1' => $s->Department_1,
            'Department_2' => $s->Department_2,
            'Class_Program_1' => $s->Class_Program_1,
            'Class_Program_2' => $s->Class_Program_2,
            'Payment_Status' => $s->Payment_Status,
            'Payment_Date' => $paymentDateIndo,
            'Payment_Type' => $s->Payment_Type,
            'Entry_Period' => $s->Entry_Period,
            'Term' => $s->Term,
            'Additional_Address' => [
                'Address' => $s->Detail_Address,
                'Zip_Code' => $s->Postal_Code,
                'Phone_Home' => $s->Phone_Home
            ]
        ];
    }

    public function show($id)
    {
        try {
            $request = new Request([
                'camaru_id' => $id,
            ]);
            return $this->index($request);
        } catch (\Exception $e) {
            return $this->errorResponse('Internal server error', $e->getMessage(), 500);
        }
    }
}
