<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasVector;

class Embedding extends Model
{
    use HasVector;

    protected $fillable = [
        'ayah_id',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'vector',
    ];

    public function ayah()
    {
        return $this->belongsTo(Ayah::class, 'ayah_id');
    }
}
