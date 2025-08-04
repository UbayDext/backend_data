<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'classroom_id',
        'studi_id',
        'ekskul_id',
    ];

    public function studi() {
        return $this->belongsTo(Studi::class);
    }
    public function classroom() {
        return $this->belongsTo(Classroom::class);
    }
    public function ekskul() {
        return $this->belongsTo(Ekskul::class);
    }
    public function getRouteKeyName(): string
    {
        return 'name';
    }
    public function getRouteKey(): string
    {
        return $this->name;
    }
    public function getFullName(): string
    {
        return $this->name . ' (' . $this->classroom->name . ')';
    }
}
