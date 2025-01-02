<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'table_number',
        'capacity',
        'status',
    ];

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
