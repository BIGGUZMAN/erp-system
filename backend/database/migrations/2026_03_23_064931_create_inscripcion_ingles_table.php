}<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inscripciones_ingles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('nivel_id');
            $table->string('ciclo_escolar'); 
            $table->enum('modalidad', ['Semanal', 'Sabatino', 'En Línea']);
            $table->string('duracion')->nullable(); 
            $table->enum('estado_pago', ['Pagado', 'Pendiente'])->default('Pendiente');
            $table->decimal('calificacion_final', 5, 2)->nullable();
            $table->enum('estado_academico', ['Aprobado', 'Reprobado', 'Cursando'])->default('Cursando');
            $table->string('ruta_comprobante')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('nivel_id')->references('id')->on('niveles_ingles')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('inscripciones_ingles');
    }
};
