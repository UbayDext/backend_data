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
        'lombad_id',
        'winner_match1',
        'winner_match2',
        'champion',
    ];

    public function lombad() {
        return $this->belongsTo(Lombad::class);
    }

       public function semifinalTeams()
    {
        return [
            [$this->name_team1, $this->name_team2],
            [$this->name_team3, $this->name_team4],
        ];
    }

     public function finalTeams()
    {
        return [
            $this->winner_match1,
            $this->winner_match2,
        ];
    }
}
