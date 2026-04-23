<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('niveles_ingles', function (Blueprint $table) {
            $table->id();
            $table->integer('numero'); 
            $table->string('nombre'); 
            $table->enum('clasificacion', ['Basico', 'Intermedio']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('niveles_ingles');
    }
};
