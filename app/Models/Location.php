<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\LocationFactory::new();
    }

    protected $fillable = [
        'name',
        'suffix',
        'ipratico_key',
        'utente_mail',
        'password_mail',
        'telefono',
        'logo',
        'prenota_web_restaurant_id',
        'slope_bearer_token',
        'slope_enabled',
    ];

    protected $casts = [
        'slope_enabled' => 'boolean',
    ];

    // location has many reservations
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
    public function closures()
    {
        return $this->hasMany(Closure::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function slopeBookings(): HasMany
    {
        return $this->hasMany(SlopeBooking::class);
    }

    /**
     * Encrypt/decrypt the Slope bearer token
     */
    protected function slopeBearerToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }
}
