<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ekskul extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama_ekskul',
        'jumlah_siswa',
        'studi_id'
    ];
     protected $casts = [
        'id' => 'integer',
        'studi_id' => 'integer',
        'students_count' => 'integer',
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
   public function ekskulAttendances() {
    return $this->hasMany(EkskulAttendances::class);
   }
   public function lombad() {
    return $this->hasMany(Lombad::class);
   }
    public function user() {
    return $this->hasMany(User::class);
   }
}
