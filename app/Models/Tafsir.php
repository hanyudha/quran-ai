<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tafsir extends Model
{
    protected $table = 'tafsirs'; // Plural table name
    
    protected $fillable = [
        'ayah_id',
        'source_id', // Changed from 'source' to 'source_id' (foreign key)
        'short_text',
        'long_text', 
        'language',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Ayah
     */
    public function ayah(): BelongsTo
    {
        return $this->belongsTo(Ayah::class);
    }

    /**
     * Relationship with TafsirSource
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(TafsirSource::class, 'source_id');
    }

    /**
     * Relationship with TafsirEmbedding
     */
    public function embedding()
    {
        return $this->hasOne(TafsirEmbedding::class);
    }

    /**
     * Scope for specific tafsir source
     */
    public function scopeBySource($query, $sourceCode)
    {
        return $query->whereHas('source', function($q) use ($sourceCode) {
            $q->where('code', $sourceCode);
        });
    }

    /**
     * Scope for language
     */
    public function scopeByLanguage($query, $language = 'id')
    {
        return $query->where('language', $language);
    }

    /**
     * Get display text (prioritize long_text, fallback to short_text)
     */
    public function getDisplayTextAttribute(): string
    {
        return $this->long_text ?? $this->short_text ?? '';
    }

    /**
     * Check if tafsir has long text
     */
    public function hasLongText(): bool
    {
        return !empty($this->long_text);
    }

    /**
     * Check if tafsir has embedding
     */
    public function hasEmbedding(): bool
    {
        return $this->embedding()->exists();
    }
}