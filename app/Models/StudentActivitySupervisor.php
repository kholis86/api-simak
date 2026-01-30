<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivitySupervisor extends Model
{
    protected $table = 'acd_student_activity_supervisor';
    protected $primaryKey = 'Activity_Supervisor_Id';
    public $timestamps = false;

    protected $fillable = [
        'Student_Activity_Id',
        'Employee_Id',
        'Pembimbing_Ke',
        'Kategori_Kegiatan',
    ];

    /* ================= RELATIONS ================= */

    public function activity()
    {
        return $this->belongsTo(
            StudentActivity::class,
            'Student_Activity_Id',
            'Student_Activity_Id'
        );
    }

    public function employee()
    {
        return $this->belongsTo(
            emp_employee::class,
            'Employee_Id',
            'Employee_Id'
        );
    }
}
