<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kids extends Model
{
    protected $fillable = [
        'invitation_id',
        'party_member_id',
        'name',
        'middle',
        'lastname',
        'is_attending',
    ];

    public function partyMember(): BelongsTo
    {
        return $this->belongsTo(PartyMember::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function attendingGuest(): BelongsTo
    {
        return $this->belongsTo(AttendingGuest::class);
    }
}
