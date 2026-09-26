<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegCamaru extends Model
{
    protected $table = 'simak_mou.reg_camaru';
    protected $primaryKey = 'Camaru_Id';
    public $timestamps = false; // Using Modified_Date as per SQL provided

    protected $fillable = [
        'Reg_Num', 'Test_Num', 'Mitra_Id', 'Entry_Year_Id', 'Entry_Term_Id',
        'Entry_Period_Sched_Id', 'Entry_Period_Type_Id', 'Register_Status_Id',
        'Register_Type_Id', 'Full_Name', 'Gender_Id', 'Religion_Id', 'Nik_Number',
        'Citizenship_Id', 'Address', 'Country_Id', 'Province_Id', 'City_Id',
        'District_Id', 'Sub_District', 'Blood_Id', 'Photo', 'Zip_Code',
        'Marital_Status_Id', 'Phone_Mobile', 'Email_General', 'Pass',
        'Activation', 'Activation_Code', 'Education_Group_Id', 'Department_Choice_1',
        'Class_Prog_Choice_1', 'Donation_Amount_Choice_1', 'Department_Choice_2',
        'Class_Prog_Choice_2', 'Donation_Amount_Choice_2', 'Registration_Date',
        'Birth_Place', 'Birth_Date', 'RT', 'RW', 'Disability', 'Nisn_Number',
        'High_School_Major_Id', 'High_School_Origin', 'High_School_Address',
        'High_School_Entry', 'High_School_Pass', 'Reveral_Type_Id', 'Test_Score',
        'Test_Date', 'Invitation_Number', 'Document_Due_Date', 'Payment_Due_Date',
        'Payment_Date', 'Reff_Payment', 'Payment_Type', 'Is_Complete', 'Is_Accepted',
        'Is_Accepted_2', 'Is_Document_Complete', 'Description', 'Description_2',
        'Verificator', 'Verificator_2', 'Electric_Bill_Amount', 'Department_Choice_Accepted',
        'Department_Accepted_Id', 'Class_Prog_Accepted_Id', 'Accepted_Donation_Amount',
        'Her_Status', 'Upload_Status', 'Nim_Origin', 'University_Origin',
        'University_Origin_Code', 'University_Origin_Name', 'Department_Program_Origin',
        'Test_Status', 'Attachment_Note', 'Is_From_Sttnas', 'Origin_Education_Type',
        'Her_Upload', 'Is_Kps', 'Branch_Id', 'Job_Category_Id', 'Created_By',
        'Created_Date', 'Modified_By', 'Modified_Date', 'Referral_Nim', 'Temp_Pass'
    ];

    public function address()
    {
        return $this->hasOne(RegCamaruAddress::class, 'Camaru_Id', 'Camaru_Id');
    }

    public function department1()
    {
        return $this->belongsTo(MstrDepartment::class, 'Department_Choice_1', 'Department_Id');
    }

    public function department2()
    {
        return $this->belongsTo(MstrDepartment::class, 'Department_Choice_2', 'Department_Id');
    }

    public function classProg1()
    {
        return $this->belongsTo(MstrClassProgram::class, 'Class_Prog_Choice_1', 'Class_Prog_Id');
    }

    public function classProg2()
    {
        return $this->belongsTo(MstrClassProgram::class, 'Class_Prog_Choice_2', 'Class_Prog_Id');
    }
}
