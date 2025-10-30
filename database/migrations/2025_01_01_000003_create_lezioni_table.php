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
        Schema::create('lezioni', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->time('ora_inizio');
            $table->time('ora_fine');
            $table->foreignId('id_docente')->nullable()->constrained('docenti')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_modulo')->nullable()->constrained('moduli')->nullOnDelete()->cascadeOnUpdate();
            $table->string('aula', 150)->nullable();
            $table->timestamps();
            
            // Indici per ricerche veloci
            $table->index('data');
            $table->index(['data', 'ora_inizio']);
            $table->index('id_docente');
            $table->index('id_modulo');
            $table->index('aula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lezioni');
    }
};

