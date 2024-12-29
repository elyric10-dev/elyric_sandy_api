<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Invitation extends Model
{
    protected $fillable = [
        'party_members_id',
        'invitation_code',
        'seat_count',
        'attended_count',
    ];
    
    protected $casts = [
        'seat_count' => 'integer',
        'attended_count' => 'integer'
    ];

    protected $appends = ['invitation_link'];

    public static function generateInvitationCode(): string
    {
        return Str::random(32);
    }

    public function getInvitationLinkAttribute(): string
    {
        return env('FRONTEND_URL') . '/rsvp/' . $this->invitation_code;
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
