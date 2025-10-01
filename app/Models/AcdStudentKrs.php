<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcdStudentKrs extends Model
{
    protected $table = 'acd_student_krs';
    protected $primaryKey = 'Krs_Id';
    public $timestamps = false;

    protected $fillable = [
        'Student_Id',
        'Term_Year_Id',
        'Course_Id',
        'Class_Prog_Id',
        'Class_Id',
        'Sks',
        'Is_Approved',
        'Cost_Item_Id',
        'Amount'
    ];

    // Relasi ke Mahasiswa
    public function student()
    {
        return $this->belongsTo(AcdStudent::class, 'Student_Id', 'Student_Id');
    }

    // Relasi ke Course
    public function course()
    {
        return $this->belongsTo(AcdCourse::class, 'Course_Id', 'Course_Id');
    }

    // Relasi ke Program Kelas
    public function classProgram()
    {
        return $this->belongsTo(MstrClassProgram::class, 'Class_Prog_Id', 'Class_Prog_Id');
    }

    // Relasi ke Kelas
    public function class()
    {
        return $this->belongsTo(MstrClass::class, 'Class_Id', 'Class_Id');
    }
}
