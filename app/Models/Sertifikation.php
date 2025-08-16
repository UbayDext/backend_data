<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sertifikation extends Model
{
    use HasFactory;

    protected $table = 'sertifikations';

    protected $fillable = [
        'title','student_id','studi_id','ekskul_id','classroom_id','file_path',
    ];

    protected $appends = ['file_url']; // <-- agar otomatis ikut di JSON

    // Relations
    public function student(){ return $this->belongsTo(Student::class); }
    public function classroom(){ return $this->belongsTo(Classroom::class); }
    public function ekskul(){ return $this->belongsTo(Ekskul::class); }

    // Accessor
    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }

    // Scopes (rapi + bisa di-chain)
    public function scopeStudent($q, $id){ return $id ? $q->where('student_id',$id) : $q; }
    public function scopeEkskul($q, $id){ return $id ? $q->where('ekskul_id',$id) : $q; }
    public function scopeStudi($q, $id){ return $id ? $q->where('studi_id',$id) : $q; }
    public function scopeClassroom($q, $id){ return $id ? $q->where('classroom_id',$id) : $q; }
    public function scopeBetweenDate($q, $from, $to){
        if ($from && $to) return $q->whereBetween('created_at', [$from, $to]);
        if ($from) return $q->whereDate('created_at','>=',$from);
        if ($to) return $q->whereDate('created_at','<=',$to);
        return $q;
    }
}

