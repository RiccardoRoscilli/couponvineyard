<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSuffixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Let's seed the database with the suffixes
        Location::where('name', 'Villa Crespi')
        ->update(['suffix' => 'VI']);
        
        Location::where('name', "Laqua Piemonte Lago d'Orta")
        ->update(['suffix' => 'LL']);

        Location::where('name', "Laqua Campania - Ticciano")
        ->update(['suffix' => 'LC']);

        Location::where('name', 'Laqua Toscana Terriciola')    
        ->update(['suffix' => 'LV']);

        Location::where('name', 'Laqua Campania - Meta di Sorrento')
        ->update(['suffix' => 'LS']);

         
    }
}
