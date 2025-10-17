<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasVector
{
    protected function casts(): array
    {
        return [
            'embedding' => 'array', // casting ke array otomatis
        ];
    }

    public static function cosineDistance(array $a, array $b): float
    {
        $dot = array_sum(array_map(fn($x, $y) => $x * $y, $a, $b));
        $magA = sqrt(array_sum(array_map(fn($x) => $x * $x, $a)));
        $magB = sqrt(array_sum(array_map(fn($x) => $x * $x, $b)));

        return 1 - ($dot / ($magA * $magB));
    }
}
