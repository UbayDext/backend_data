<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EkskulAttendances extends Model
{
    use HasFactory;

    protected $fillable = [
    'student_id',
    'ekskul_id',
    'studi_id',
    'tanggal',
    'status',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'ekskul_id' => 'integer',
    ];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function ekskul() {
        return $this->belongsTo(Ekskul::class);
    }

    public function studi() {
        return $this->belongsTo(Studi::class);
    }
}
