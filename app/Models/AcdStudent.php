<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcdStudent extends Model
{
    protected $table = 'acd_student';
    protected $primaryKey = 'Student_Id';

    public $timestamps = false; // kalau tabel tidak pakai created_at/updated_at

    protected $fillable = [
        'Nim',
        'Register_Number',
        'Full_Name',
        'Department_Id',
        'Class_Prog_Id',
        'Bhirt_Place',
        'Bhirt_Date',
        'Entry_Year',
        'Entry_Term_Id',
        'Religion',
        'Marital_Status_Id',
        'Nisn',
        'Nik',
        'Email_Corporate',
        'Phone_Mobile'
    ];

    // Relasi ke Department
    public function department()
    {
        return $this->belongsTo(MstrDepartment::class, 'Department_Id', 'Department_Id');
    }

    // Relasi ke Class Program
    public function classProgram()
    {
        return $this->belongsTo(MstrClassProgram::class, 'Class_Prog_Id', 'Class_Prog_Id');
    }

    // Relasi ke Religion
    public function religion()
    {
        return $this->belongsTo(MstrReligion::class, 'Religion_Id', 'Religion_Id');
    }

    // Relasi ke Marital Status
    public function maritalStatus()
    {
        return $this->belongsTo(MstrMaritalStatus::class, 'Marital_Status_Id', 'Marital_Status_Id');
    }

    //relasi krs
    public function krs()
    {
        return $this->hasMany(AcdStudentKrs::class, 'Student_Id', 'Student_Id');
    }
}
