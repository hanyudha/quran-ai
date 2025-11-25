<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surah extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'name_ar', 'name_id', 'translation', 
        'revelation', 'number_of_ayahs', 'description',
        'audio', 'bismillah_ar', 'bismillah_id', 'bismillah_audio'
    ];

    protected $casts = [
        'bismillah_audio' => 'array',
    ];

    /**
     * Relationship with Ayahs
     */
    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class);
    }

    /**
     * Get ayahs with tafsirs
     */
    public function ayahsWithTafsirs()
    {
        return $this->ayahs()->with('tafsirs.source');
    }

    /**
     * Scope by revelation type
     */
    public function scopeByRevelation($query, $type)
    {
        return $query->where('revelation', $type);
    }

    /**
     * Get next surah
     */
    public function nextSurah(): ?Surah
    {
        return self::where('number', '>', $this->number)
            ->orderBy('number')
            ->first();
    }

    /**
     * Get previous surah  
     */
    public function previousSurah(): ?Surah
    {
        return self::where('number', '<', $this->number)
            ->orderByDesc('number')
            ->first();
    }
}