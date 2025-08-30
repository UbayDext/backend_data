<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lombad extends Model
{
    use HasFactory;

    protected $table = 'lombads';
    protected $fillable = [
        'name',
        'status',
        'ekskul_id'
    ];

    protected $casts = [
        'ekskul_id' => 'integer',
    ];

    public function ekskul() {
        return $this->belongsTo(Ekskul::class);
    }
    public function individuRace() {
        return $this->hasMany(IndividuRace::class);
    }
    public function teamRace() {
        return $this->hasMany(TeamRace::class);
    }
}
