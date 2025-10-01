<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrClass extends Model
{
    protected $table = 'mstr_class';
    protected $primaryKey = 'Class_Id';
    public $timestamps = false;

    protected $fillable = [
        'Class_Id',
        'Class_Name'
    ];

    public function krs()
    {
        return $this->hasMany(AcdStudentKrs::class, 'Class_Id', 'Class_Id');
    }
}
