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

    protected $casts = [
        'id' => 'integer',
        'classroom_id' => 'integer',
        'studi_id' => 'integer',
        'ekskul_id' => 'integer'
    ];
    public function studi() {
        return $this->belongsTo(Studi::class, 'studi_id');
    }
    public function classroom() {
        return $this->belongsTo(Classroom::class);
    }
    public function ekskul() {
        return $this->belongsTo(Ekskul::class);
    }
public function sertifikats()
{
    return $this->belongsToMany(Sertifikation::class, 'sertifikat_student')
        ->withTimestamps();
}

   public function sertifikations()
{
    return $this->hasMany(\App\Models\Sertifikation::class, 'student_id');
}
   public function ekskulAttendances() {
    return $this->hasMany(EkskulAttendances::class);
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
   public function latestSertifikation()
{
    return $this->hasOne(\App\Models\Sertifikation::class, 'student_id')->latestOfMany();
}
    public function raceParticipants()
{
    return $this->hasMany(\App\Models\IndividuRaceParticipan::class, 'student_id');
    // atau IndividuRaceParticipant::class jika kamu pakai nama yg benar
}

}
