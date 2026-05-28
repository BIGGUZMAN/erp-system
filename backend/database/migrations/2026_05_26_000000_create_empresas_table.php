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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id('id_empresa');
            $table->integer('anio');
            $table->string('empresa')->unique();
            $table->string('tipo_empresa');
            $table->string('rfc')->nullable()->unique();
            $table->string('direccion')->nullable();
            $table->string('tipo_convenio');
            $table->date('fecha_firma')->nullable();
            $table->integer('vigencia'); // en años
            $table->date('fecha_termino')->nullable();
            $table->string('convenio_fisico')->nullable(); // Sí / No / Detalles
            $table->string('representante')->nullable();
            $table->string('cargo')->nullable();
            $table->string('contacto')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            
            // Carreras beneficiadas
            $table->boolean('igem')->default(false);
            $table->boolean('itics')->default(false);
            $table->boolean('ilog')->default(false);
            $table->boolean('ind')->default(false);
            $table->boolean('idam')->default(false);
            $table->boolean('ife')->default(false);
            
            // Seguimiento
            $table->text('proyectos')->nullable();
            $table->text('comentarios')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
