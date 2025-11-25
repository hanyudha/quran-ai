<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ayah extends Model
{
    use HasFactory;

    protected $fillable = [
        'surah_id', 
        'ayah_in_quran', 
        'ayah_in_surah',
        'text_ar', 
        'text_id', 
        'audio', 
        'image', 
        // 'tafsir', // Remove this - we have separate tafsirs table
        'meta'
    ];

    protected $casts = [
        'audio' => 'array',
        'image' => 'array',
        // 'tafsir' => 'array', // Remove this
        'meta' => 'array',
    ];

    /**
     * Relationship with Surah
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    /**
     * Relationship with Embedding
     */
    public function embedding(): HasOne
    {
        return $this->hasOne(Embedding::class);
    }

    /**
     * Relationship with Tafsirs (multiple tafsirs per ayah)
     */
    public function tafsirs(): HasMany
    {
        return $this->hasMany(Tafsir::class);
    }

    /**
     * Get specific tafsir by source code
     */
    public function tafsirBySource(string $sourceCode): ?Tafsir
    {
        return $this->tafsirs()
            ->whereHas('source', function($query) use ($sourceCode) {
                $query->where('code', $sourceCode);
            })
            ->first();
    }

    /**
     * Get Kemenag tafsir (convenience method)
     */
    public function getKemenagTafsirAttribute(): ?Tafsir
    {
        return $this->tafsirBySource('kemenag');
    }

    /**
     * Get Quraish tafsir (convenience method)
     */
    public function getQuraishTafsirAttribute(): ?Tafsir
    {
        return $this->tafsirBySource('quraish');
    }

    /**
     * Get Jalalayn tafsir (convenience method)
     */
    public function getJalalaynTafsirAttribute(): ?Tafsir
    {
        return $this->tafsirBySource('jalalayn');
    }

    /**
     * Check if ayah has embedding
     */
    public function hasEmbedding(): bool
    {
        return $this->embedding()->exists();
    }

    /**
     * Check if ayah has tafsirs
     */
    public function hasTafsirs(): bool
    {
        return $this->tafsirs()->exists();
    }

    /**
     * Scope for ayahs that have embeddings
     */
    public function scopeWithEmbedding($query)
    {
        return $query->has('embedding');
    }

    /**
     * Scope for ayahs that have tafsirs
     */
    public function scopeWithTafsirs($query)
    {
        return $query->has('tafsirs');
    }

    /**
     * Scope by surah
     */
    public function scopeInSurah($query, $surahId)
    {
        return $query->where('surah_id', $surahId);
    }

    /**
     * Get next ayah in Quran
     */
    public function nextAyah(): ?Ayah
    {
        return self::where('ayah_in_quran', '>', $this->ayah_in_quran)
            ->orderBy('ayah_in_quran')
            ->first();
    }

    /**
     * Get previous ayah in Quran
     */
    public function previousAyah(): ?Ayah
    {
        return self::where('ayah_in_quran', '<', $this->ayah_in_quran)
            ->orderByDesc('ayah_in_quran')
            ->first();
    }

    /**
     * Get formatted ayah reference (Surah Name: Ayah Number)
     */
    public function getReferenceAttribute(): string
    {
        return "{$this->surah->name_id}:{$this->ayah_in_surah}";
    }

    /**
     * Get formatted ayah reference with Arabic name
     */
    public function getReferenceWithArabicAttribute(): string
    {
        return "{$this->surah->name_ar} ({$this->surah->name_id}):{$this->ayah_in_surah}";
    }
}