<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ayah extends Model
{
    protected $fillable = [
        'surah_id', 'ayah_in_quran', 'ayah_in_surah',
        'text_ar', 'text_id', 'audio', 'image', 'tafsir', 'meta'
    ];

    protected $casts = [
        'audio' => 'array',
        'image' => 'array',
        'tafsir' => 'array',
        'meta' => 'array',
    ];

    public function surah()
    {
        return $this->belongsTo(Surah::class);
    }

    public function embedding()
    {
        return $this->hasOne(Embedding::class);
    }
}

