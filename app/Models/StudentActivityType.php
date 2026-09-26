<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivityType extends Model
{
    protected $table = 'acd_student_activity_type';
    protected $primaryKey = 'Student_Activity_Type_Id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Student_Activity_Type_Id',
        'Activity_Type_Code',
        'Activity_Type_Name',
        'Is_Kampus_Merdeka',
        'Activity_Type_Kind',
    ];

    protected $casts = [
        'Is_Kampus_Merdeka' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(
            StudentActivity::class,
            'Student_Activity_Type_Id',
            'Student_Activity_Type_Id'
        );
    }
}
