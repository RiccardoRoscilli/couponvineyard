<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::select('id', 'name')->get();
        Log::info('Locations:', $locations->toArray());
        return response()->json($locations);
    }
}
