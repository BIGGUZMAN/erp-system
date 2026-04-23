<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('numero_control')->unique();
            $table->string('correo')->unique();
            $table->string('password_hash')->nullable();
            $table->string('rol')->default('user');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('carrera_id')->nullable();
            $table->timestamps();

            // Foreign key hacia carreras
            $table->foreign('carrera_id')->references('id_carrera')->on('carreras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
