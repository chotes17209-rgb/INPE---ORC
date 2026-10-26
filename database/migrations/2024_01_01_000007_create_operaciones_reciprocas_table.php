<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operaciones_reciprocas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_operacion')->unique();
            $table->enum('tipo_servicio', [
                'energia_electrica', 'agua_saneamiento', 'telefonia_fija',
                'telefonia_movil', 'internet', 'contrato_estado',
                'alquiler', 'convenio_interinstitucional', 'pago_municipio', 'otro'
            ]);
            $table->string('entidad_proveedora');
            $table->string('razon_social')->nullable();
            $table->string('direccion_entidad')->nullable();
            $table->string('ruc_entidad', 11)->nullable();
            $table->string('numero_contrato')->nullable();
            $table->string('servicio_prestado')->nullable();
            $table->date('vigencia_contrato_inicio')->nullable();
            $table->date('vigencia_contrato_fin')->nullable();
            $table->string('usuario_servicio')->nullable();
            $table->string('penal_pertenece')->nullable();
            // Factura / comprobante
            $table->string('factura_serie', 10)->nullable();
            $table->string('numero_factura')->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_facturacion')->nullable();
            $table->date('fecha_vencimiento');
            // Importes
            $table->decimal('base_imponible', 12, 2)->nullable();
            $table->decimal('monto', 12, 2);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('otros', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            // Orden de servicio / SIAF
            $table->string('orden_servicio')->nullable();
            $table->date('fecha_orden_servicio')->nullable();
            $table->unsignedBigInteger('registro_siaf')->nullable();
            // Periodo
            $table->integer('periodo_mes');
            $table->integer('periodo_anio');
            // Estado / Pago
            $table->enum('estado', ['pendiente', 'vencido', 'pagado', 'en_proceso', 'observado'])->default('pendiente');
            $table->enum('metodo_pago', ['transferencia', 'cheque', 'efectivo', 'debito_automatico'])->nullable();
            $table->string('numero_voucher')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->string('area_responsable')->nullable();
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
        Schema::dropIfExists('operaciones_reciprocas');
    }
};
