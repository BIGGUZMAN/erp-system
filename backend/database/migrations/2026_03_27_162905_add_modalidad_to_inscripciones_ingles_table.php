<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones_ingles', function (Blueprint $table) {
            // Agregamos duracion
            if (!Schema::hasColumn('inscripciones_ingles', 'duracion')) {
                $table->string('duracion')->nullable()->after('modalidad');
            }
            // Agregamos fechas
            if (!Schema::hasColumn('inscripciones_ingles', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('duracion');
            }
            if (!Schema::hasColumn('inscripciones_ingles', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }
        });
    }

    public function down(): void
    {
        
    }
};