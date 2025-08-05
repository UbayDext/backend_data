<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
class Classroom extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'jumlah_siswa',
        'studi_id'
    ];

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function studi() {
        return $this->belongsTo(Studi::class);
    }
   public function sertifikation() {
    return $this->hasMany(Sertifikation::class);
   }

}
