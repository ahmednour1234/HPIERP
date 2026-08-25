<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'date', 'check_in', 'check_out', 'status','lang','late','time_late','expected_hours','worked_hours','note'];

    public function admins()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
}
