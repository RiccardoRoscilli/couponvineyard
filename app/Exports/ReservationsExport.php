<?php

namespace App\Exports;

use App\Models\Reservation;
use App\Models\Location;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReservationsExport implements FromCollection, WithHeadings
{
    protected $status;
    protected $locationId;
    protected $user;

    public function __construct($status, $locationId, $user)
    {
        $this->status = $status;
        $this->locationId = $locationId;
        $this->user = $user;
    }

    public function collection()
    {
        $locationId = $this->locationId;

        if ($this->user->is_admin == 0) {
            $locationId = $this->user->location_id;
        }

        if (empty($locationId)) {
            $locationId = Location::pluck('id')->toArray();
        } else {
            $locationId = explode(',', $locationId);
        }

        return Reservation::whereIn('location_id', $locationId)
            ->where('status', $this->status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                $acquirente = !empty($r->company_cliente) ? $r->company_cliente : $r->nome_cliente . ' ' . $r->cognome_cliente;
                return [
                    $r->coupon_code,
                    $r->nome_activity,
                    $acquirente,
                    $r->nome_beneficiario . ' ' . $r->cognome_beneficiario,
                    $r->email_beneficiario,
                    $r->telefono_beneficiario,
                    $r->databooking ? Carbon::parse($r->databooking)->format('d/m/Y') : '',
                    $r->orabooking ? Carbon::parse($r->orabooking)->format('H:i') : '',
                    $r->data_fattura ? Carbon::parse($r->data_fattura)->format('d/m/Y') : '',
                    $r->n_fattura,
                    $r->amount,
                    $r->data_scadenza ? Carbon::parse($r->data_scadenza)->format('d/m/Y') : '',
                    $r->n_tavolo,
                    $r->n_camera,
                    $r->note_beneficiario,
                    $r->status,
                ];
            });
    }

    public function headings(): array
    {
        return ['Codice', 'Attività', 'Acquirente', 'Beneficiario', 'Email Beneficiario', 'Tel Beneficiario', 'Data Prenotazione', 'Ora', 'Data Fattura', 'N. Fattura', 'Importo', 'Scadenza', 'Tavolo', 'Camera', 'Note', 'Stato'];
    }
}
