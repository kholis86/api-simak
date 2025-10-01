<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MstrDepartment;
use App\Models\MstrClassProgram;
use App\Models\MstrReligion;
use App\Models\MstrMaritalStatus;

class MasterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/master/departments",
     *     tags={"Master"},
     *     summary="Get department list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Department list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_department list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Department_Id", type="integer", example=1),
     *                     @OA\Property(property="Department_Name", type="string", example="Hukum")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */
    public function departments(Request $request)
    {
        $query = MstrDepartment::select('Department_Id', 'Department_Name');

        // filter by Department_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Department_Name', 'like', '%' . $request->search . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'mstr_department list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/program-classes",
     *     tags={"Master"},
     *     summary="Get program class list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Program class list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_program_class list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Program_Class_Id", type="integer", example=1),
     *                     @OA\Property(property="Program_Class_Name", type="string", example="Reguler")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function classPrograms(Request $request)
    {
        $query = MstrClassProgram::select('Class_Prog_Id', 'Class_Prog_Name');

        // filter by Class_Prog_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Class_Prog_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'mstr_class_program list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/religions",
     *     tags={"Master"},
     *     summary="Get religion list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Religion list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_religion list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Religion_Id", type="integer", example=1),
     *                     @OA\Property(property="Religion_Name", type="string", example="Islam")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function religions(Request $request)
    {
        $query = MstrReligion::select('Religion_Id', 'Religion_Name');

        // filter by Religion_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Religion_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'religion list retrieved successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/marital-statuses",
     *     tags={"Master"},
     *     summary="Get marital status list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Marital status list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="mstr_marital_status list retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Marital_Status_Id", type="integer", example=1),
     *                     @OA\Property(property="Marital_Status_Name", type="string", example="Belum Menikah")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function maritalStatuses(Request $request)
    {
        $query = MstrMaritalStatus::select('Marital_Status_Id', 'Marital_Status_Name');

        // filter by Marital_Status_Name
        if ($request->has('search') && $request->search !== '') {
            $query->where('Marital_Status_Name', 'like', '%' . $request->name . '%');
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'marital_status list retrieved successfully',
            'data'    => $data
        ], 200);
    }
}
