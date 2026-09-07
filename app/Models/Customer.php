<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_id', 'name', 'name_en', 'mobile', 'email', 'image', 'state', 'city',
        'zip_code', 'address', 'balance', 'credit', 'type', 'latitude', 'longitude',
        'active', 'limit', 'company_id', 'category_id', 'specialist', 'region_id',
        'pharmacy_name',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class,'user_id');
    }
    /**
     * التخصص الطبي للعميل (categories.type = 0).
     *
     * ملاحظة: عمود specialist شيء آخر تمامًا يحمل نوع الجهة
     * (1=صيدلية، 2=مركز طبي، 3=مستشفى، 4=طبيب) كرقم ثابت.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

      public function regions()
    {
        return $this->belongsto(Region::class,'region_id');
    }
}
