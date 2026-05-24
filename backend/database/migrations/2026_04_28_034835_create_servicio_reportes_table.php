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
    Schema::create('servicio_reportes', function (Blueprint $table) {
        $table->id();
        $table->string('usuario_id');
        $table->integer('numero_reporte'); // 1, 2 o 3
        $table->date('fecha_inicio_periodo');
        $table->date('fecha_limite');
        $table->string('ruta_archivo')->nullable(); // Nulo hasta que se suba
        $table->string('estado')->default('Bloqueado'); // Bloqueado, Pendiente, Entregado
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_reportes');
    }
};
