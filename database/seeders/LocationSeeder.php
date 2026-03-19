<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      Location::create([
         'name' => 'Villa Crespi',
      ]);

      Location::create([
         "name" => "Laqua Piemonte Lago d'Orta",
      ]);

      Location::create([
         'name' => 'Laqua Campania - Ticciano',
      ]);

      Location::create([
         'name' => 'Laqua Toscana Terriciola',
      ]);

      Location::create([
         'name' => 'Laqua Campania - Meta di Sorrento',
      ]);
    }
}
