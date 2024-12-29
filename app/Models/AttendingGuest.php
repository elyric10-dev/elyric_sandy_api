<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendingGuest extends Model
{
    protected $fillable = [
        'invitation_id',
        'party_member_id',
        'guest_id',
        'name',
        'middle',
        'lastname'
    ];

    
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function partyMember(): BelongsTo
    {
        return $this->belongsTo(PartyMember::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
