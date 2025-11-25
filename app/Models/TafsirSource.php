<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TafsirSource extends Model
{
    protected $table = 'tafsir_sources';
    
    protected $fillable = [
        'code',
        'name', 
        'author',
        'language',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Tafsirs
     */
    public function tafsirs(): HasMany
    {
        return $this->hasMany(Tafsir::class, 'source_id');
    }

    /**
     * Scope by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Get tafsir count for this source
     */
    public function getTafsirCountAttribute(): int
    {
        return $this->tafsirs()->count();
    }
}