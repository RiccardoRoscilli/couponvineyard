<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DebugExpirationController extends Controller
{
    public function index()
    {
        $totalReservations = Reservation::where('status', 'In Attesa')->count();
        
        $reservations = Reservation::with('location')
            ->where('status', 'In Attesa')
            ->limit(10)
            ->get();
        
        $results = [];
        
        foreach ($reservations as $reservation) {
            $dataFattura = Carbon::parse($reservation->data_fattura);
            $baseExpirationDate = clone $dataFattura;
            $baseExpirationDate->addMonths(6);
            
            $closurePeriods = Closure::where('location_id', $reservation->location_id)->get();
            
            $closureDetails = [];
            $totalClosureDays = 0;
            
            foreach ($closurePeriods as $period) {
                $closureStart = Carbon::parse($period->start_date);
                $closureEnd = Carbon::parse($period->end_date);
                
                // Se la chiusura termina prima della data fattura o inizia dopo la scadenza base, skip
                if ($closureEnd->lt($dataFattura) || $closureStart->gt($baseExpirationDate)) {
                    $closureDetails[] = [
                        'start' => $period->start_date,
                        'end' => $period->end_date,
                        'counts' => false,
                        'days' => 0
                    ];
                    continue;
                }
                
                // Calcola l'inizio effettivo dell'intersezione
                $intersectionStart = $closureStart->gte($dataFattura) ? $closureStart : $dataFattura;
                
                // Calcola la fine effettiva dell'intersezione
                $intersectionEnd = $closureEnd->lte($baseExpirationDate) ? $closureEnd : $baseExpirationDate;
                
                // Calcola i giorni di intersezione
                $closureDays = $intersectionStart->diffInDays($intersectionEnd);
                $totalClosureDays += $closureDays;
                
                $closureDetails[] = [
                    'start' => $period->start_date,
                    'end' => $period->end_date,
                    'intersection_start' => $intersectionStart->format('Y-m-d'),
                    'intersection_end' => $intersectionEnd->format('Y-m-d'),
                    'counts' => true,
                    'days' => $closureDays
                ];
            }
            
            $newExpirationDate = clone $dataFattura;
            $newExpirationDate->addMonths(6)->addDays($totalClosureDays);
            
            $results[] = [
                'id' => $reservation->id,
                'coupon_code' => $reservation->coupon_code,
                'location_name' => $reservation->location->name,
                'location_id' => $reservation->location_id,
                'data_fattura' => $reservation->data_fattura,
                'data_scadenza_attuale' => $reservation->data_scadenza,
                'data_scadenza_base' => $baseExpirationDate->format('Y-m-d'),
                'closures_count' => $closurePeriods->count(),
                'closure_details' => $closureDetails,
                'total_closure_days' => $totalClosureDays,
                'data_scadenza_calcolata' => $newExpirationDate->format('Y-m-d'),
                'is_correct' => $reservation->data_scadenza == $newExpirationDate->format('Y-m-d')
            ];
        }
        
        return view('debug.expiration', [
            'total' => $totalReservations,
            'results' => $results
        ]);
    }
    
    public function update()
    {
        $updated = 0;
        $reservations = Reservation::with('location')
            ->where('status', 'In Attesa')
            ->get();
        
        foreach ($reservations as $reservation) {
            $dataFattura = Carbon::parse($reservation->data_fattura);
            $baseExpirationDate = clone $dataFattura;
            $baseExpirationDate->addMonths(6);
            
            $closurePeriods = Closure::where('location_id', $reservation->location_id)->get();
            $totalClosureDays = 0;
            
            foreach ($closurePeriods as $period) {
                $closureStart = Carbon::parse($period->start_date);
                $closureEnd = Carbon::parse($period->end_date);
                
                // Se la chiusura termina prima della data fattura o inizia dopo la scadenza base, skip
                if ($closureEnd->lt($dataFattura) || $closureStart->gt($baseExpirationDate)) {
                    continue;
                }
                
                // Calcola l'inizio effettivo dell'intersezione
                $intersectionStart = $closureStart->gte($dataFattura) ? $closureStart : $dataFattura;
                
                // Calcola la fine effettiva dell'intersezione
                $intersectionEnd = $closureEnd->lte($baseExpirationDate) ? $closureEnd : $baseExpirationDate;
                
                // Calcola i giorni di intersezione
                $closureDays = $intersectionStart->diffInDays($intersectionEnd);
                $totalClosureDays += $closureDays;
            }
            
            $newExpirationDate = clone $dataFattura;
            $newExpirationDate->addMonths(6)->addDays($totalClosureDays);
            
            if ($reservation->data_scadenza != $newExpirationDate->format('Y-m-d')) {
                $reservation->update(['data_scadenza' => $newExpirationDate]);
                $updated++;
            }
        }
        
        return redirect()->route('debug.expiration')->with('success', "Aggiornate {$updated} prenotazioni");
    }
}
