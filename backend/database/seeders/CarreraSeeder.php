<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos la tabla primero para evitar duplicados si lo corres varias veces
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('carreras')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $carreras = [
            ['id_carrera' => 1, 'nombre' => 'Ingeniería en Sistemas Computacionales', 'total_alumnos' => 0],
            ['id_carrera' => 2, 'nombre' => 'Ingeniería en Tecnologías de la Información y Comunicaciones', 'total_alumnos' => 0],
            ['id_carrera' => 3, 'nombre' => 'Ingeniería en Gestión Empresarial', 'total_alumnos' => 0],
            ['id_carrera' => 4, 'nombre' => 'Ingeniería Industrial', 'total_alumnos' => 0],
            ['id_carrera' => 5, 'nombre' => 'Ingeniería en Logística', 'total_alumnos' => 0],
            ['id_carrera' => 6, 'nombre' => 'Ingeniería Ambiental', 'total_alumnos' => 0],
        ];

        DB::table('carreras')->insert($carreras);
    }
}