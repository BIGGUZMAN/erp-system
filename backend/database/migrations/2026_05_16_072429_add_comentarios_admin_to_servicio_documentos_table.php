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
        Schema::table('servicio_documentos', function (Blueprint $table) {
            // Creamos la columna faltante en la base de datos
            $table->text('comentarios_admin')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio_documentos', function (Blueprint $table) {
            // En caso de rollback, eliminamos la columna
            $table->dropColumn('comentarios_admin');
        });
    }
}; 