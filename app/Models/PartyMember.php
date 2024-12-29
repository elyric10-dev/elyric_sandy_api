<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartyMember extends Model
{

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
