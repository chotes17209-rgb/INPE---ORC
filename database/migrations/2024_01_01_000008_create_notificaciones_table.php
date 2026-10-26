<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('tipo', ['info', 'warning', 'danger', 'success'])->default('info');
            $table->string('modulo')->nullable();
            $table->string('url_accion')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_sistema');
    }
};
