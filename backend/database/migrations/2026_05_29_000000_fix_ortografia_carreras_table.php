<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('carreras')->where('nombre', 'Ingeniera Industrial')->update(['nombre' => 'Ingeniería Industrial']);
        DB::table('carreras')->where('nombre', 'Ingeniera Ferroviaria')->update(['nombre' => 'Ingeniería Ferroviaria']);
        DB::table('carreras')->where('nombre', 'Ingeniera Ambiental')->update(['nombre' => 'Ingeniería Ambiental']);
        DB::table('carreras')->where('nombre', 'Ingeniera en Logstica')->update(['nombre' => 'Ingeniería en Logística']);
        DB::table('carreras')->where('nombre', 'Ingeniera en Gestin Empresarial')->update(['nombre' => 'Ingeniería en Gestión Empresarial']);
        DB::table('carreras')->where('nombre', 'Ingeniera en Tecnologas de la Informacin y Comunicaciones')->update(['nombre' => 'Ingeniería en Tecnologías de la Información y Comunicaciones']);
        DB::table('carreras')->where('nombre', 'Ingeniera Industrial (Virtual)')->update(['nombre' => 'Ingeniería Industrial (Virtual)']);
        DB::table('carreras')->where('nombre', 'Ingeniera en Gestin Empresarial (Virtual)')->update(['nombre' => 'Ingeniería en Gestión Empresarial (Virtual)']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('carreras')->where('nombre', 'Ingeniería Industrial')->update(['nombre' => 'Ingeniera Industrial']);
        DB::table('carreras')->where('nombre', 'Ingeniería Ferroviaria')->update(['nombre' => 'Ingeniera Ferroviaria']);
        DB::table('carreras')->where('nombre', 'Ingeniería Ambiental')->update(['nombre' => 'Ingeniera Ambiental']);
        DB::table('carreras')->where('nombre', 'Ingeniería en Logística')->update(['nombre' => 'Ingeniera en Logstica']);
        DB::table('carreras')->where('nombre', 'Ingeniería en Gestión Empresarial')->update(['nombre' => 'Ingeniera en Gestin Empresarial']);
        DB::table('carreras')->where('nombre', 'Ingeniería en Tecnologías de la Información y Comunicaciones')->update(['nombre' => 'Ingeniera en Tecnologas de la Informacin y Comunicaciones']);
        DB::table('carreras')->where('nombre', 'Ingeniería Industrial (Virtual)')->update(['nombre' => 'Ingeniera Industrial (Virtual)']);
        DB::table('carreras')->where('nombre', 'Ingeniería en Gestión Empresarial (Virtual)')->update(['nombre' => 'Ingeniera en Gestin Empresarial (Virtual)']);
    }
};
