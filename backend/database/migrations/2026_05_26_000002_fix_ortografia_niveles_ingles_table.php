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
        DB::table('niveles_ingles')->where('nombre', 'Bsico 1')->update(['nombre' => 'Básico 1']);
        DB::table('niveles_ingles')->where('nombre', 'Bsico 2')->update(['nombre' => 'Básico 2']);
        DB::table('niveles_ingles')->where('nombre', 'Bsico 3')->update(['nombre' => 'Básico 3']);
        DB::table('niveles_ingles')->where('nombre', 'Bsico 4')->update(['nombre' => 'Básico 4']);
        DB::table('niveles_ingles')->where('nombre', 'Bsico 5')->update(['nombre' => 'Básico 5']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('niveles_ingles')->where('nombre', 'Básico 1')->update(['nombre' => 'Bsico 1']);
        DB::table('niveles_ingles')->where('nombre', 'Básico 2')->update(['nombre' => 'Bsico 2']);
        DB::table('niveles_ingles')->where('nombre', 'Básico 3')->update(['nombre' => 'Bsico 3']);
        DB::table('niveles_ingles')->where('nombre', 'Básico 4')->update(['nombre' => 'Bsico 4']);
        DB::table('niveles_ingles')->where('nombre', 'Básico 5')->update(['nombre' => 'Bsico 5']);
    }
};
