<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guest;

class GlobalSettingsController extends Controller
{
    public function getServerDate()
    {
        return response()->json([
            'server_date' => now()->format('Y-m-d')
        ]);
    }

    public function unAttendAllExpiredGuests()
    {
        $guests = Guest::where('is_attending', null)->get();

        foreach ($guests as $guest) {
            $guest->is_attending = 0;
            $guest->save();
        }

        return response()->json([
            'message' => 'All the guests on ' . env('DEADLINE_DATE') . ' are unattended',
            'guests' => $guests
        ]);
    }

}
