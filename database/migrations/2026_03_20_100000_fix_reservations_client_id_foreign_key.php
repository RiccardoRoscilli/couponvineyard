<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Controlla se esiste la foreign key errata client_id -> locations
        $fk = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'reservations' 
              AND TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'client_id' 
              AND REFERENCED_TABLE_NAME = 'locations'
        ");

        if (!empty($fk)) {
            Schema::table('reservations', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk[0]->CONSTRAINT_NAME);
            });
        }
    }

    public function down(): void
    {
        // Non ripristinare la FK errata
    }
};
