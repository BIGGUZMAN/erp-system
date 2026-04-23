<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->string('nombre')->unique();
            $table->unsignedInteger('total_alumnos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
