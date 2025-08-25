<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndividuRaceParticipan extends Model
{
    use HasFactory;
    protected $fillable = [
        'individu_race_id',
        'student_id',
        'point1',
        'point2',
        'point3',
        'point4',
        'point5',
    ];
        protected $table = 'individu_race_participans'; 
    protected $casts = [
        'point1'=>'integer',
        'point2'=>'integer',
        'point3'=>'integer',
        'point4'=>'integer',
        'point5'=>'integer',
    ];
    protected $appends = ['total'];
    public function race() {
        return $this->belongsTo(IndividuRace::class, 'individu_race_id');
    }
    public function getTotalAtribute(): int {
        return ($this->pont1+$this->point2+$this->point3+$this->point4+$this->point5);
    }
    public function student() {
        return $this->belongsTo(Student::class);
    }
}
