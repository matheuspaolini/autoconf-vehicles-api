<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->string('placa', 7)->unique();
            $table->string('chassi', 17)->unique();
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('versao', 100);
            $table->decimal('valor_venda', 15, 2);
            $table->string('cor', 100);
            $table->unsignedBigInteger('km');
            $table->enum('cambio', ['manual', 'automatico']);
            $table->enum('combustivel', ['gasolina', 'alcool', 'flex', 'diesel', 'hibrido', 'eletrico']);
            $table->timestamps();
            $table->index(['marca', 'modelo']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
