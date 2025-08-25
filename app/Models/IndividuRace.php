<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class IndividuRace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_lomba',
        'ekskul_id',
        'start_date',
        'end_date',
        'status',
        'lombad_id', // penting!
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
    
    protected $appends = ['status_effective'];

    // normalisasi input status (lowercase -> 'Berlangsung'/'Selesai')
    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucfirst(strtolower($value))
        );
    }

    protected function statusEffective(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = strtolower($this->attributes['status'] ?? '');
                if ($raw !== 'berlangsung') {
                    return ucfirst($raw);
                }
                return $this->end_date?->isPast() ? 'Selesai' : 'Berlangsung';
            }
        );
    }

    // optional scopes untuk filter
    public function scopeBerlangsung($q) { return $q->where('status', 'Berlangsung'); }
    public function scopeSelesai($q)     { return $q->where('status', 'Selesai'); }
}
