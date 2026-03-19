<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IpraticoAPIService;

class IpraticoPromoCodeController extends Controller
{
    public function index()
    {
        $promoCodes = IpraticoAPIService::api('GET', 'promo-codes', '20154:4f93c386-8366-46d4-9b42-0fa251c644d6', );

        if (isset($promoCodes->error)) {
            return response()->json([
                'message' => 'Errore durante la richiesta a iPratico.',
                'error' => $promoCodes->error
            ], 500);
        }

        return response()->json($promoCodes);
    }
}

