<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sertifikation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'student_id',
        'studi_id',
        'ekskul_id',
        'classroom_id',
        'sertifikat_file',
    ];
    public function student() {
        return $this->belongsTo(Student::class);
    }
    public function classroom() {
        return $this->belongsTo(Classroom::class);
    }
    public function ekskul() {
        return $this->belongsTo(Ekskul::class);
    }
}
