<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpEmployee extends Model
{
    protected $table = 'emp_employee';
    protected $primaryKey = 'Employee_Id';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    /* ==========================
     |  MASS ASSIGNMENT
     |========================== */
    protected $fillable = [
        'Nik',
        'Nip',
        'Name',
        'First_Title',
        'Last_Title',
        'Full_Name',
        'Birth_Place',
        'Birth_Date',
        'Address',
        'Address_Identity',
        'Gender_Id',
        'Religion_Id',
        'Identity_Type_Id',
        'Identity_Number',
        'Bank_Id',
        'Rec_Num',
        'Phone_Mobile',
        'Phone_Home',
        'Employee_Status_Id',
        'Blood_Type_Id',
        'Marital_Status_Id',
        'Nbm',
        'Nidn',
        'Email_General',
        'Email_Corporate',
        'Role',
        'Active_Status_Id',
        'Npwp',
        'Npwp_Document',
        'Npwp_Document_Name',
        'Nik_Salary',
        'Photos',
        'Password',
        'Nik_Finger_Print',
        'Fingerprint_Id',
        'Document_Serdos',
        'Document_Serdos_Ext',
        'Work_Unit_Id',
        'Department_Id',
        'Employee_Role',
        'Forum_Role',
        'Payroll_Role',
        'internal_eksternal',
        'Rfid',
        'Card_Accepted',
        'Nbm_Document',
        'Nbm_Document_Name',
        'Nbm_Url',
        'Identity_Document',
        'Identity_Document_Name',
        'Identity_Url',
        'Bpjs_Document',
        'Bpjs_Document_Name',
        'Bpjs_Url',
        'Health_Insurance_Document',
        'Health_Insurance_Document_Name',
        'Health_Insurance_Url',
        'Lecturer_Certification_Document_Name',
        'Lecturer_Certification_Document',
        'Lecturer_Certification_Url',
        'Family_Card_Document_Name',
        'Family_Card_Document',
        'Family_Card_Url',
        'Employee_Id_Moodle',
        'Created_By',
        'Created_Date',
        'Modified_By',
        'Modified_Date',
    ];

    /* ==========================
     |  CASTS
     |========================== */
    protected $casts = [
        'Birth_Date'    => 'datetime',
        'Created_Date'  => 'datetime',
        'Modified_Date' => 'datetime',

        'Gender_Id'          => 'integer',
        'Religion_Id'        => 'integer',
        'Identity_Type_Id'   => 'integer',
        'Bank_Id'            => 'integer',
        'Employee_Status_Id' => 'integer',
        'Blood_Type_Id'      => 'integer',
        'Marital_Status_Id'  => 'integer',
        'Active_Status_Id'   => 'integer',
        'Work_Unit_Id'       => 'integer',
        'Department_Id'      => 'integer',
    ];

    /* ==========================
     |  ACCESSORS
     |========================== */

    /**
     * Nama lengkap + gelar (untuk dosen)
     */
    public function getDisplayNameAttribute(): string
    {
        return trim(
            ($this->First_Title ? $this->First_Title . ' ' : '') .
            ($this->Full_Name ?? $this->Name ?? '') .
            ($this->Last_Title ? ', ' . $this->Last_Title : '')
        );
    }

    /* ==========================
     |  QUERY SCOPES
     |========================== */

    /**
     * Hanya dosen (punya NIDN)
     */
    public function scopeDosen($query)
    {
        return $query->whereNotNull('Nidn');
    }

    /**
     * Pegawai aktif
     */
    public function scopeActive($query)
    {
        return $query->where('Active_Status_Id', 1);
    }

    /* ==========================
     |  RELATIONS (FK BASED)
     |========================== */

    public function department()
    {
        return $this->belongsTo(
            MstrDepartment::class,
            'Department_Id',
            'Department_Id'
        );
    }

    public function workUnit()
    {
        return $this->belongsTo(
            EmpWorkUnit::class,
            'Work_Unit_Id',
            'Work_Unit_Id'
        );
    }

    public function employeeStatus()
    {
        return $this->belongsTo(
            EmpEmployeeStatus::class,
            'Employee_Status_Id',
            'Employee_Status_Id'
        );
    }

    public function activeStatus()
    {
        return $this->belongsTo(
            EmpActiveStatus::class,
            'Active_Status_Id',
            'Active_Status_Id'
        );
    }

    public function gender()
    {
        return $this->belongsTo(
            MstrGender::class,
            'Gender_Id',
            'Gender_Id'
        );
    }

    public function religion()
    {
        return $this->belongsTo(
            MstrReligion::class,
            'Religion_Id',
            'Religion_Id'
        );
    }
}
