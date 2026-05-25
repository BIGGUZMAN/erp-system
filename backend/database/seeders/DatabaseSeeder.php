<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CarreraSeeder::class);

        // Seed default users for local testing
        DB::table('usuarios')->insert([
            [
                'numero_control' => '221130029',
                'correo' => '221130029@gamadero.tecnm.mx',
                'nombre_completo' => 'Juan Alumno Perez',
                'password_hash' => Hash::make('alumno123'),
                'rol' => 'alumno',
                'is_active' => true,
                'carrera_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'numero_control' => 'ADMIN01',
                'correo' => 'admin@gamadero.tecnm.mx',
                'nombre_completo' => 'Admin General',
                'password_hash' => Hash::make('admin123'),
                'rol' => 'admin',
                'is_active' => true,
                'carrera_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'numero_control' => 'CLE01',
                'correo' => 'cle.admin@gamadero.tecnm.mx',
                'nombre_completo' => 'Admin Inglés',
                'password_hash' => Hash::make('ingles123'),
                'rol' => 'admin_ingles',
                'is_active' => true,
                'carrera_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
