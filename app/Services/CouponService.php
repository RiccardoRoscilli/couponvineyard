<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Closure;
use Carbon\Carbon;

class CouponService
{
    public function updateCouponExpirationDates()
    {
        // Recupera solo le prenotazioni con stato 'In Attesa'
        $reservations = Reservation::with('location')
                                    ->where('status', 'In Attesa')
                                    ->get();

        foreach ($reservations as $reservation) {
            // Calcola la data di scadenza base (data_fattura + 6 mesi)
            $dataFattura = Carbon::parse($reservation->data_fattura);
            $baseExpirationDate = clone $dataFattura;
            $baseExpirationDate->addMonths(6);

            // Recupera i periodi di chiusura associati al locale della prenotazione
            $closurePeriods = Closure::where('location_id', $reservation->location_id)->get();

            // Calcola i giorni di chiusura che cadono DENTRO il periodo di validità del coupon
            $totalClosureDays = 0;
            
            foreach ($closurePeriods as $period) {
                $closureStart = Carbon::parse($period->start_date);
                $closureEnd = Carbon::parse($period->end_date);
                
                // Calcola l'intersezione tra il periodo di chiusura e il periodo di validità del coupon
                // Solo i giorni che cadono DENTRO il periodo di validità devono essere contati
                
                // Se la chiusura termina prima della data fattura o inizia dopo la scadenza base, skip
                if ($closureEnd->lt($dataFattura) || $closureStart->gt($baseExpirationDate)) {
                    continue;
                }
                
                // Calcola l'inizio effettivo dell'intersezione (il più tardi tra data_fattura e closure_start)
                $intersectionStart = $closureStart->gte($dataFattura) ? $closureStart : $dataFattura;
                
                // Calcola la fine effettiva dell'intersezione (il più presto tra base_expiration e closure_end)
                $intersectionEnd = $closureEnd->lte($baseExpirationDate) ? $closureEnd : $baseExpirationDate;
                
                // Calcola i giorni di intersezione
                $closureDays = $intersectionStart->diffInDays($intersectionEnd);
                $totalClosureDays += $closureDays;
            }

            // Calcola la nuova data di scadenza aggiungendo solo i giorni di chiusura rilevanti
            $newExpirationDate = clone $dataFattura;
            $newExpirationDate->addMonths(6)->addDays($totalClosureDays);

            // Aggiorna la data di scadenza della prenotazione
            $reservation->update(['data_scadenza' => $newExpirationDate]);
        }
    }
}
