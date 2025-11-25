<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TafsirEmbedding extends Model
{
    protected $table = 'tafsir_embeddings';
    
    protected $fillable = [
        'tafsir_id',
        'embedding',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Tafsir
     */
    public function tafsir(): BelongsTo
    {
        return $this->belongsTo(Tafsir::class);
    }

    /**
     * Scope for similarity search (without index on Windows)
     */
    public function scopeSimilarTo($query, array $vector, int $limit = 10)
    {
        $vectorString = '[' . implode(',', $vector) . ']';
        
        return $query->orderByRaw('embedding <=> ?', [$vectorString])
                    ->limit($limit);
    }
}