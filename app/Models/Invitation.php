<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Invitation extends Model
{
    protected $fillable = [
        'name',
        'middle',
        'lastname',
        'invitation_code',
        'seat_count',
        'is_attending',
    ];
    
    protected $casts = [
        'is_attending' => 'boolean',
        'seat_count' => 'integer'
    ];

    protected $appends = ['invitation_link'];

    public function partyMembers(): HasMany
    {
        return $this->hasMany(PartyMember::class);
    }

    public static function generateInvitationCode(): string
    {
        return Str::random(32);
    }

    public function getInvitationLinkAttribute(): string
    {
        return env('FRONTEND_URL') . '/rsvp/' . $this->invitation_code;
    }
}
