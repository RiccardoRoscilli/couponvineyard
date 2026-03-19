<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->longText('description_en')->nullable()->after('description');
            $table->longText('details_en')->nullable()->after('details');
            $table->longText('note_en')->nullable()->after('note');
            $table->longText('prenotare_en')->nullable()->after('prenotare');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'name_en',
                'description_en',
                'details_en',
                'note_en',
                'prenotare_en',
            ]);
        });
    }
};
