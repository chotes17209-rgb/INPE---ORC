<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique();
            $table->string('marca', 60);
            $table->string('modelo', 60);
            $table->year('anio_fabricacion');
            $table->string('color', 40)->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('numero_motor')->nullable();
            $table->enum('tipo', ['sedan', 'camioneta', 'furgon', 'moto', 'bus', 'camion', 'otro']);
            $table->enum('combustible', ['gasolina', 'diesel', 'electrico', 'hibrido', 'gnv']);
            $table->decimal('valor_adquisicion', 12, 2)->default(0);
            $table->date('fecha_adquisicion')->nullable();
            $table->integer('kilometraje_actual')->default(0);
            $table->integer('kilometraje_siguiente_mantenimiento')->nullable();
            $table->date('fecha_soat')->nullable();
            $table->date('fecha_revision_tecnica')->nullable();
            $table->date('fecha_seguro')->nullable();
            $table->enum('estado', ['operativo', 'mantenimiento', 'baja', 'disponible', 'asignado'])->default('disponible');
            $table->string('area_asignada')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->string('imagen')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
