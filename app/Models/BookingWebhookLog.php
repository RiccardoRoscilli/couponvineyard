<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingWebhookLog extends Model
{
    protected $fillable = [
        'booking_id', 'location_id', 'id_prenotazione', 'last_modified',
        'http_status', 'success', 'error_message',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}