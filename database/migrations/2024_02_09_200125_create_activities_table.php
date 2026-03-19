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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');
            $table->string('description')->nullable();
            $table->string('details')->nullable();
            $table->string('note')->nullable();
            $table->integer('product_value')->nullable();
            $table->string('ipratico_category_id')->nullable();
            $table->string('ipratico_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
