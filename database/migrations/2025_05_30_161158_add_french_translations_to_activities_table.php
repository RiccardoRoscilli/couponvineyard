<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('name_fr')->nullable()->after('name_en');
            $table->text('description_fr')->nullable()->after('description_en');
            $table->text('details_fr')->nullable()->after('details_en');
            $table->text('note_fr')->nullable()->after('note_en');
            $table->text('prenotare_fr')->nullable()->after('prenotare_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            //
        });
    }
};
