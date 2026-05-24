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
    Schema::create('servicio_documentos', function (Blueprint $table) {
        $table->id();
        // Usamos string porque tus números de control pueden llevar letras (E, C, B)
        $table->string('usuario_id'); 
        $table->string('tipo_documento'); // Kardex, Carta de Presentación, etc.
        $table->string('ruta_archivo');
        $table->string('estado')->default('Pendiente'); // Pendiente, Aceptado, Rechazado
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_documentos');
    }
};
