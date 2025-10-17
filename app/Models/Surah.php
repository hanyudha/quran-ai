<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Surah extends Model
{
    protected $fillable = [
        'number', 'name_ar', 'name_id', 'translation',
        'revelation', 'number_of_ayahs', 'description',
        'audio', 'bismillah_ar', 'bismillah_id', 'bismillah_audio'
    ];

    protected $casts = [
        'bismillah_audio' => 'array',
    ];

    public function ayahs()
    {
        return $this->hasMany(Ayah::class);
    }
}

