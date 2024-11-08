<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSettings extends Model
{
    protected $fillable = [
        'is_locked'
    ];
}
