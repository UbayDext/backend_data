<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studi extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama_studi',
    ];

    public function student() {
        return $this->hasMany(Student::class);
    }
    public function classroom() {
        return $this->hasMany(Classroom::class);
    }
    public function ekskul() {
        return $this->hasMany(Ekskul::class);
    }
    public function getRouteKeyName(): string
    {
        return 'nama_studi';
    }
}
