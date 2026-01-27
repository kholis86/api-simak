<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstrTermYear extends Model
{
    use HasFactory;

    protected $table = 'mstr_term_year';
    protected $primaryKey = 'Term_Year_Id';
    public $timestamps = false;
    
    // ... (kolom fillable, dll.)

    /**
     * Atribut yang harus di-casting ke tipe data native.
     * Menggunakan 'date:Y-m-d' akan memaksa Eloquent memformat
     * tanggal menjadi YYYY-MM-DD saat diubah ke array/JSON.
     * * @var array<string, string>
     */
    protected $casts = [
        'Term_Year_Id' => 'integer',
        'Year_Id' => 'integer',
        'Term_Id' => 'integer',

        // ✨ Perubahan di sini: Tentukan format output yang diinginkan
        'Start_Date' => 'datetime:Y-m-d H:i',
        'End_Date' => 'datetime:Y-m-d H:i',
    ];
}
