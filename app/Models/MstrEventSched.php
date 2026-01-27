<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstrEventSched extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model ini.
     * @var string
     */
    protected $table = 'mstr_event_sched';

    /**
     * Kunci utama (primary key) dari tabel.
     * @var string
     */
    protected $primaryKey = 'Event_Sched_Id';

    /**
     * Tipe data dari primary key, karena ID Anda mungkin bukan integer default (misal: Big Integer).
     * Jika Anda yakin ID-nya adalah integer biasa, Anda bisa menghapus baris ini.
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     * @var array<int, string>
     */
    protected $fillable = [
        'Event_Id',
        'Department_Id',
        'Term_Year_Id',
        'Is_Open',
        'Start_Date',
        'End_Date',
        'End_Date_Cost', // Asumsi ini adalah tanggal juga
        'Created_By',
        'Created_Date',
        'Modified_By',
        'Modified_Date',
    ];

    /**
     * Nonaktifkan fitur timestamps Laravel (created_at dan updated_at)
     * karena Anda menggunakan kolom 'Created_Date' dan 'Modified_Date' secara eksplisit.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Mendefinisikan kolom tanggal (Date/Time) yang harus diubah menjadi instance Carbon.
     * Ini membantu dalam format dan manipulasi tanggal.
     * @var array<int, string>
     */
    protected $dates = [
        'Start_Date',
        'End_Date',
        'End_Date_Cost',
        'Created_Date',
        'Modified_Date',
    ];
}
