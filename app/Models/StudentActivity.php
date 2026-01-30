<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivity extends Model
{
    protected $table = 'acd_student_activity';
    protected $primaryKey = 'Student_Activity_Id';
    public $timestamps = false;

    protected $fillable = [
        'Program_MBKM',
        'Jenis_Anggota',
        'Jenis_Aktivitas',
        'Department_Id',
        'Term_Year_Id',
        'Judul',
        'Keterangan',
        'Lokasi',
        'No_SK_Tugas',
        'Tanggal_SK_Tugas',
        'Tanggal_Mulai',
        'Tanggal_Selesai',
        'Created_At',
        'Updated_At',
    ];

    /* ================= RELATIONS ================= */

    public function department()
    {
        return $this->belongsTo(
            MstrDepartment::class,
            'Department_Id',
            'Department_Id'
        );
    }

    public function termYear()
    {
        return $this->belongsTo(
            MstrTermYear::class,
            'Term_Year_Id',
            'Term_Year_Id'
        );
    }

    public function members()
    {
        return $this->hasMany(
            StudentActivityMember::class,
            'Student_Activity_Id',
            'Student_Activity_Id'
        );
    }

    public function supervisors()
    {
        return $this->hasMany(
            StudentActivitySupervisor::class,
            'Student_Activity_Id',
            'Student_Activity_Id'
        );
    }
}
