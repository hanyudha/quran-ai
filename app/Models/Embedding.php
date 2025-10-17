<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Embedding extends Model
{
    protected $fillable = ['ayah_id', 'embedding'];
    public $timestamps = true;

    /**
     * Simpan embedding manual (convert array ke format vector Postgres)
     */
    public static function storeVector($ayahId, array $vector)
    {
        $vectorString = '[' . implode(',', $vector) . ']';

        DB::statement('
            INSERT INTO embeddings (ayah_id, embedding, created_at, updated_at)
            VALUES (?, ?::vector, NOW(), NOW())
            ON CONFLICT (ayah_id) DO UPDATE SET embedding = EXCLUDED.embedding, updated_at = NOW()
        ', [$ayahId, $vectorString]);
    }

    public function ayah()
    {
        return $this->belongsTo(Ayah::class);
    }
}
