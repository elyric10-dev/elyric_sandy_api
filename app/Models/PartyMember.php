<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyMember extends Model
{
    protected $fillable = [
        'name',
        'middle',
        'lastname',
        'invitation_id'
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
