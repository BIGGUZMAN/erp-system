<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_documentos', function (Blueprint $table) {
            $table->longText('ruta_archivo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('servicio_documentos', function (Blueprint $table) {
            $table->string('ruta_archivo')->change();
        });
    }
};
