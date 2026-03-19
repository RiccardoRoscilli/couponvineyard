<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'name_fr',
        'description',
        'description_en',
        'description_fr',
        'details',
        'details_en',
        'details_fr',
        'note',
        'note_en',
        'note_fr',
        'prenotare',
        'prenotare_en',
        'prenotare_fr',
        'sku',
        'location_id',
        'product_value',
        'ipratico_id',
        'ipratico_category_id',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
