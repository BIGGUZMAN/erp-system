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
        Schema::table('inscripciones_ingles', function (Blueprint $table) {
            // Creamos la columna grupo como string, con valor por defecto 'A'
            // Se coloca físicamente después de la columna modalidad
            $table->string('grupo', 10)->default('A')->after('modalidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones_ingles', function (Blueprint $table) {
            // Esto sirve para deshacer el cambio si ejecutas php artisan migrate:rollback
            $table->dropColumn('grupo');
        });
    }
};