<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegCamaruAddress extends Model
{
    protected $table = 'simak_mou.reg_camaru_address';
    protected $primaryKey = 'Camaru_Address_Id';
    public $timestamps = false;

    protected $fillable = [
        'Address_Category_Id', 'Camaru_Id', 'City_Id', 'District_Id',
        'Address', 'Postal_Code', 'Phone_Home', 'Created_By',
        'Created_Date', 'Modified_By', 'Modified_Date'
    ];

    public function camaru()
    {
        return $this->belongsTo(RegCamaru::class, 'Camaru_Id', 'Camaru_Id');
    }
}
