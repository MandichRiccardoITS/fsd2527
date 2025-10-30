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
        Schema::create('moduli', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('unita_formativa', 150);
            $table->timestamps();
            
            // Indici per ricerche veloci
            $table->index('nome');
            $table->index('unita_formativa');
            
            // Indice composito per evitare duplicati
            $table->unique(['nome', 'unita_formativa'], 'uk_modulo_uf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moduli');
    }
};

