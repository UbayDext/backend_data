<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_group',
        'name_team1',
        'name_team2',
        'name_team3',
        'name_team4',
        'name_team5',
        'lombad_id',
        'champion'
    ];

    public function lombad() {
        return $this->belongsTo(Lombad::class);
    }
}
