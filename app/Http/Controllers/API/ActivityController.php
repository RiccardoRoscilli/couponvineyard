<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::select('id', 'name', 'location_id', 'details', 'product_value')->get();
        Log::info('Activities:', $activities->toArray());
        return response()->json($activities);
    }
}
