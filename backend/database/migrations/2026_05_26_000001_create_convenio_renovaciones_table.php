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
        Schema::create('convenio_renovaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->date('fecha_firma_anterior')->nullable();
            $table->date('fecha_termino_anterior')->nullable();
            $table->integer('vigencia_anterior')->nullable();
            $table->date('nueva_fecha_firma')->nullable();
            $table->date('nueva_fecha_termino')->nullable();
            $table->integer('nueva_vigencia')->nullable();
            $table->text('comentarios')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')
                  ->references('id_empresa')
                  ->on('empresas')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convenio_renovaciones');
    }
};
