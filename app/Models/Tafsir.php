<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tafsir extends Model
{
    //
    protected $table = 'tafsir';
    protected $fillable = ['ayah_id', 'source', 'text'];
    public function ayah()
    {
        return $this->belongsTo(Ayah::class);
    }
}
