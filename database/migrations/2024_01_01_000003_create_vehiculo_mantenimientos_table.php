<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo_mantenimiento', ['preventivo', 'correctivo', 'emergencia']);
            $table->string('descripcion');
            $table->date('fecha_programada');
            $table->date('fecha_realizado')->nullable();
            $table->decimal('costo_estimado', 10, 2)->default(0);
            $table->decimal('costo_real', 10, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->string('taller')->nullable();
            $table->string('tecnico_responsable')->nullable();
            $table->integer('kilometraje_mantenimiento')->nullable();
            $table->enum('estado', ['programado', 'en_proceso', 'completado', 'cancelado'])->default('programado');
            $table->text('observaciones')->nullable();
            // Orden de Servicio / SIAF / Factura
            $table->string('orden_servicio')->nullable();
            $table->date('fecha_orden_servicio')->nullable();
            $table->unsignedBigInteger('registro_siaf')->nullable();
            $table->string('factura_numero')->nullable();
            $table->string('factura_serie', 10)->nullable();
            $table->string('factura_numero_completo', 20)->nullable();
            $table->date('factura_fecha')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_mantenimientos');
    }
};
