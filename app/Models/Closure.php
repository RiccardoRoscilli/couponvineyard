<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    use HasFactory;

    protected $fillable = ['location_id', 'start_date', 'end_date'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}

