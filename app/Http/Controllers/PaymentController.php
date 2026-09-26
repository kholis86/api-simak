<?php

namespace App\Http\Controllers;

use App\Helpers\CheckJenisToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/student-payment",
     *     summary="Get Student Payment Bill",
     *     description="Mengambil data tagihan pembayaran mahasiswa menggunakan stored procedure usp_GetStudentBill.",
     *     operationId="getStudentBill",
     *     security={{"bearerAuth":{}}},
     *     tags={"Payment"},
     *     @OA\Parameter(
     *         name="register_number",
     *         in="query",
     *         required=false,
     *         description="Nomor Registrasi Mahasiswa (Wajib jika login sebagai Admin/api-token)",
     *         @OA\Schema(type="string", example="202301001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil data tagihan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data tagihan berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getStudentBill(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $userData = $request->user();

            if ($tokenName == 'mahasiswa-token') {
                // Jika login sebagai mahasiswa, paksa menggunakan Register_Number miliknya sendiri
                $regNumber = $userData->Register_Number ?? $userData->Nim;
            } else {
                // Jika login sebagai admin (api-token), ambil dari parameter query
                $regNumber = $request->query('register_number');
            }

            if (!$regNumber) {
                return $this->errorResponse(
                    'Parameter required',
                    ($tokenName == 'mahasiswa-token') 
                        ? 'Register Number tidak ditemukan pada profil mahasiswa' 
                        : 'Admin wajib menyertakan parameter register_number',
                    422
                );
            }

            // Aman dari SQL Injection karena menggunakan parameter binding (?)
            $results = DB::select('CALL usp_GetStudentBill(?,?,?)', [$regNumber, '', '']);

            return $this->successResponse('Data tagihan berhasil diambil', $results);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data tagihan', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-channels",
     *     summary="Get Payment Channels",
     *     description="Mengambil daftar channel pembayaran yang aktif.",
     *     operationId="getPaymentChannels",
     *     security={{"bearerAuth":{}}},
     *     tags={"Payment"},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil daftar channel pembayaran",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar channel pembayaran berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getPaymentChannels(Request $request)
    {
        try {
            $data = DB::table('fnc_payment_channel')
                ->leftJoin('fnc_payment_method', 'fnc_payment_method.Payment_Method_Id', '=', 'fnc_payment_channel.Payment_Method_Id')
                ->select(
                    'fnc_payment_channel.Payment_Channel_Id',
                    'fnc_payment_channel.Fee',
                    DB::raw('CONCAT(fnc_payment_channel.Payment_Channel_Id, "-", fnc_payment_channel.Fee) as Id_Payments'),
                    DB::raw('CONCAT(fnc_payment_method.Payment_Method_Name, " - ", fnc_payment_channel.Payment_Channel_Name) as Payments')
                )
                ->where('fnc_payment_channel.Is_Active', 1)
                ->get();

            return $this->successResponse('Daftar channel pembayaran berhasil diambil', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar channel pembayaran', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-history",
     *     summary="Get Student Payment History",
     *     description="Mengambil riwayat pembayaran mahasiswa.",
     *     operationId="getPaymentHistory",
     *     security={{"bearerAuth":{}}},
     *     tags={"Payment"},
     *     @OA\Parameter(
     *         name="register_number",
     *         in="query",
     *         required=false,
     *         description="Nomor Registrasi Mahasiswa (Wajib jika login sebagai Admin/api-token)",
     *         @OA\Schema(type="string", example="202301001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil riwayat pembayaran",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Riwayat pembayaran berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $userData = $request->user();

            if ($tokenName == 'mahasiswa-token') {
                $regNumber = $userData->Register_Number ?? $userData->Nim;
            } else {
                $regNumber = $request->query('register_number');
            }

            if (!$regNumber) {
                return $this->errorResponse('Parameter required', 'Register Number diperlukan', 422);
            }

            $result = DB::table('fnc_reff_payment')
                ->select(DB::raw('Reff_Payment_Id, Payment_Date, fnc_bank.Bank_Name, sum(fnc_reff_payment.Total_Amount) as Total_Amount'))
                ->join('fnc_bank', 'fnc_reff_payment.Bank_Id', '=', 'fnc_bank.Bank_Id')
                ->where('fnc_reff_payment.Register_Number', $regNumber)
                ->groupBy('Reff_Payment_Id', 'Payment_Date', 'fnc_bank.Bank_Name')
                ->orderBy('fnc_reff_payment.Payment_Date', 'DESC')
                ->get();


            return $this->successResponse('Riwayat pembayaran berhasil diambil', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil riwayat pembayaran', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-history/detail",
     *     summary="Get Payment History Detail",
     *     description="Mengambil detail item dari satu referensi pembayaran.",
     *     operationId="getPaymentDetail",
     *     security={{"bearerAuth":{}}},
     *     tags={"Payment"},
     *     @OA\Parameter(
     *         name="reff_payment_id",
     *         in="query",
     *         required=true,

     *         description="ID Referensi Pembayaran",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil detail pembayaran",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail pembayaran berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getPaymentDetail(Request $request)
    {
        try {
            $request->validate([
                'reff_payment_id' => 'required'
            ]);

            $reffPaymentId = $request->input('reff_payment_id');
            $tokenName = CheckJenisToken::getName($request);
            $userData = $request->user();

            $query = DB::table('fnc_student_payment as pay')
                ->leftJoin('fnc_cost_item as ci', 'pay.Cost_Item_Id', '=', 'ci.Cost_Item_Id')
                ->leftJoin('mstr_term_year as ty', 'pay.Term_Year_Id', '=', 'ty.Term_Year_Id')
                ->join('fnc_reff_payment as reff', 'pay.Reff_Payment_Id', '=', 'reff.Reff_Payment_Id')
                ->where('pay.Reff_Payment_Id', $reffPaymentId);

            // Keamanan: Jika mahasiswa, pastikan Reff_Payment_Id miliknya
            if ($tokenName == 'mahasiswa-token') {
                $regNumber = $userData->Register_Number ?? $userData->Nim;
                $query->where('reff.Register_Number', $regNumber);
            }

            $result = $query->select(
                'pay.*',
                'ci.Cost_Item_Name',
                'ty.Term_Year_Name'
            )->get();

            if ($result->isEmpty()) {
                return $this->errorResponse('Data tidak ditemukan', 'Detail pembayaran tidak ditemukan atau Anda tidak memiliki akses', 404);
            }

            return $this->successResponse('Detail pembayaran berhasil diambil', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail pembayaran', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/active-payment",
     *     summary="Check Active Payment/Invoice",
     *     description="Mengecek apakah mahasiswa memiliki tagihan/invoice yang masih aktif (belum kadaluarsa).",
     *     operationId="getActivePayment",
     *     security={{"bearerAuth":{}}},
     *     tags={"Payment"},
     *     @OA\Parameter(
     *         name="register_number",
     *         in="query",
     *         required=false,
     *         description="Nomor Registrasi Mahasiswa (Wajib jika login sebagai Admin/api-token)",
     *         @OA\Schema(type="string", example="202301001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengecek tagihan aktif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data tagihan aktif berhasil diambil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getActivePayment(Request $request)
    {
        try {
            $tokenName = CheckJenisToken::getName($request);
            $userData = $request->user();

            if ($tokenName == 'mahasiswa-token') {
                $regNumber = $userData->Register_Number ?? $userData->Nim;
            } else {
                $regNumber = $request->query('register_number');
            }

            if (!$regNumber) {
                return $this->errorResponse('Parameter required', 'Register Number diperlukan', 422);
            }

            $dataBayar = DB::table('fnc_epayment_history')
                ->select(
                    'fnc_epayment_history.*',
                    'fnc_payment_channel.*',
                    DB::raw('CONCAT(fnc_payment_method.Payment_Method_Name, " - ", fnc_payment_channel.Payment_Channel_Name) as Payments')
                )
                ->leftJoin('fnc_payment_channel', 'fnc_payment_channel.Payment_Channel_Id', '=', 'fnc_epayment_history.Payment_Channel_Id')
                ->leftJoin('fnc_payment_method', 'fnc_payment_method.Payment_Method_Id', '=', 'fnc_payment_channel.Payment_Method_Id')
                ->where('fnc_epayment_history.Register_Number', $regNumber)
                ->where('fnc_epayment_history.Expired_Date', '>', DB::raw('NOW()'))
                ->orderBy('fnc_epayment_history.Expired_Date', 'DESC')
                ->first();


            if (!$dataBayar) {
                return $this->successResponse('Tidak ada tagihan aktif', [
                    'is_active' => false,
                    'data' => null
                ]);
            }

            return $this->successResponse('Data tagihan aktif berhasil diambil', [
                'is_active' => true,
                'data' => $dataBayar
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data tagihan aktif', $e->getMessage(), 500);
        }
    }
}


