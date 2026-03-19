<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'location_id', 'restaurant_id', 'date_from', 'date_to', 'api_version',
        'request_url', 'http_status', 'success', 'error_message',
        'response_json', 'num_reservations', 'executed_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'success' => 'boolean',
        'response_json' => 'array',
        'executed_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(BookingWebhookLog::class);
    }
}