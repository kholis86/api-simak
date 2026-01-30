<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivityMember extends Model
{
    protected $table = 'acd_student_activity_member';
    protected $primaryKey = 'Activity_Member_Id';
    public $timestamps = false;

    protected $fillable = [
        'Student_Activity_Id',
        'Student_Id',
        'Jenis_Peran',
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

    public function student()
    {
        return $this->belongsTo(
            Student::class,
            'Student_Id',
            'Student_Id'
        );
    }
}
