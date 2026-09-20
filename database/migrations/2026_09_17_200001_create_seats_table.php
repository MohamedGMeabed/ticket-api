<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->string('row');
            $table->integer('number');
            $table->unsignedInteger('price_cents');
            $table->enum('status', ['available', 'held', 'booked'])->default('available');
            $table->timestamps();

            $table->unique(['event_id', 'section', 'row', 'number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
