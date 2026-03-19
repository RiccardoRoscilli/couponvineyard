<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlopeBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'slope_booking_id',
        'data',
        'ora',
        'cliente',
        'telefono',
        'email',
        'lingua',
        'newsletter',
        'note_int',
        'stato',
        'situazione',
        'departure_date',
        'adults',
        'children',
        'is_canceled',
        'last_update_date',
        'synced_at',
    ];

    protected $casts = [
        'data' => 'date',
        'departure_date' => 'date',
        'ora' => 'datetime',
        'last_update_date' => 'datetime',
        'synced_at' => 'datetime',
        'is_canceled' => 'boolean',
    ];

    // Valori possibili per il campo stato
    public const STATUS_ATTIVA = 'Attiva';
    public const STATUS_MODIFICATA = 'Modificata';
    public const STATUS_CANCELLATA = 'Cancellata';

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
