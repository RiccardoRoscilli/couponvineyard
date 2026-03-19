<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'location_id',
        'order_id',
        'users_amount',
        'experience_label',
        'invoice_id',
        'invoice_date',
        'coupon_code',
        'email_beneficiario',
        'nome_beneficiario',
        'cognome_beneficiario',
        'telefono_beneficiario',
        'databooking',
        'orabooking',
        'note_beneficiario',
        'status',
        'ipratico_id',
        'ipratico_client_id',
        'data_scadenza',
        'n_tavolo',
        'n_camera',
        'invoice_increment',
    ];

    // reservation belongs to location
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // reservation belongs to client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
