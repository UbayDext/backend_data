<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class IndividuRace extends Model
{
    protected $fillable = ['name_lomba', 'ekskul_id', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected $appends = ['status_effective'];

    public function getStatusEffectiveAttribute(): string
    {
        return now()->toDateString() > $this->end_date->toDateString()
            ? 'selesai' : 'berlangsung';
    }

    protected static function booted(): void
    {
        static::saving(function ($m) {
            $m->status = now()->toDateString() > $m->end_date->toDateString()
                ? 'selesai' : 'berlangsung';
        });
    }
}
