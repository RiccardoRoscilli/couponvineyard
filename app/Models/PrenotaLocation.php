<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrenotaLocation extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'location_id', 'is_enabled'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}