<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('servicio_reportes', function (Blueprint $table) {
            // Agregamos la columna como nullable por si no siempre hay comentarios
            $table->text('comentarios_admin')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_reportes', function (Blueprint $table) {
            $table->dropColumn('comentarios_admin');
        });
    }


};
