<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alimentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_registro')->unique();
            $table->enum('penal', ['HYO', 'HVCA', 'AYACUCHO'])->nullable();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('orden_servicio')->nullable();
            $table->date('fecha_orden_servicio')->nullable();
            $table->string('registro_siaf')->nullable();
            $table->string('numero_contrato')->nullable();
            $table->string('garantia_descripcion')->nullable();
            $table->decimal('garantia_monto', 12, 2)->nullable();
            $table->date('garantia_vencimiento')->nullable();
            $table->string('factura_serie', 10)->nullable();
            $table->string('factura_numero', 20)->nullable();
            $table->date('fecha_emision');
            $table->string('proveedor_ruc', 11)->nullable();
            $table->string('proveedor_nombre')->nullable();
            $table->string('proveedor_razon_social');
            $table->decimal('base_imponible', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('otros', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['pendiente', 'en_proceso', 'pagado', 'vencido', 'observado'])->default('pendiente');
            $table->string('area_responsable')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->enum('metodo_pago', ['transferencia', 'cheque', 'efectivo', 'debito_automatico'])->nullable();
            $table->string('numero_voucher')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('archivo_adjunto')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentos');
    }
};
