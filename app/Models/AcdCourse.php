<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcdCourse extends Model
{
    protected $table = 'acd_course';
    protected $primaryKey = 'Course_Id';
    public $timestamps = false;

    protected $fillable = [
        'Course_Id',
        'Department_Id',
        'Course_Type_Id',
        'Course_Group_Id',
        'Course_Code',
        'Course_Name',
        'Course_Name_Eng',
        'Sks_Tm',
        'Sks_Prak',
        'Sks_Prak_Lap',
        'Sks_Sim'
    ];
    protected $appends = ['Sks_Total'];

    public function krs()
    {
        return $this->hasMany(AcdStudentKrs::class, 'Course_Id', 'Course_Id');
    }

    public function getSksTotalAttribute()
    {
        return (int) ($this->Sks_Tm ?? 0)
            + (int) ($this->Sks_Prak ?? 0)
            + (int) ($this->Sks_Prak_Lap ?? 0)
            + (int) ($this->Sks_Sim ?? 0);
    }
}
