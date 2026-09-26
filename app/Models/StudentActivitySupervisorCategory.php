<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivitySupervisorCategory extends Model
{
    protected $table = 'acd_student_activity_supervisor_category';
    protected $primaryKey = 'Activity_Supervisor_Category_Id';
    public $timestamps = false;

    protected $fillable = [
        'Category_Code',
        'Category_Name',
        'Created_By',
        'Created_Date',
        'Modified_By',
        'Modified_Date',
    ];

    public function supervisors()
    {
        return $this->hasMany(
            StudentActivitySupervisor::class,
            'Activity_Supervisor_Category_Id',
            'Activity_Supervisor_Category_Id'
        );
    }
}
