<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guest;
use App\Models\AttendingGuest;
use App\Models\Table;
use App\Models\Kids;

class DashboardController extends Controller
{
    public function index()
    {
        //get all guests count, accepted, rejected, arrived, absent, kids, tablescount
        $guests = Guest::all();
        $guestsCount = count($guests);
        $acceptedGuests = count(AttendingGuest::all());
        $rejectedGuests = Guest::where('is_attending', 0)->count();
        $noResponse = Guest::where('is_attending', null)->count();
        $arrivedGuests = AttendingGuest::where('status', 'arrived')->count();
        $absentGuests = AttendingGuest::where('status', 'not-arrived')->count();
        $tablesCount = Table::all()->count();
        $kidsCount = Kids::all()->count();

        return response()->json([
            'Invited Guests' => $guestsCount,
            'Attending Guests' => $acceptedGuests,
            'Rejected Guests' => $rejectedGuests,
            'No Response' => $noResponse,
            'Tables Count' => $tablesCount,
            'Kids Count' => $kidsCount,
            'Arrived Guests' => $arrivedGuests,
            'Absent Guests' => $absentGuests,
        ]);
    }
}
