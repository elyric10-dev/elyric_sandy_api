<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    protected $fillable = [
        'invitation_id',
        'party_member_id',
        'name',
        'middle',
        'lastname',
        'is_attending',
        'replacement_name',
        'replacement_middle',
        'replacement_lastname',
        'replacement_is_attending',
    ];

    protected $casts = [
        'is_attending' => 'boolean',
        'replacement_is_attending' => 'boolean',
    ];

    // protected $with = ['invitation', 'partyMember'];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function partyMember(): BelongsTo
    {
        return $this->belongsTo(PartyMember::class);
    }
}
