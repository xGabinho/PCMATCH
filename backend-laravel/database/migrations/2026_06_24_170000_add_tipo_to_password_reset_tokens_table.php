<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a 'tipo' column to password_reset_tokens to support
     * multi-model auth (usuarios, bodegas, proveedores).
     * Also changes the primary key to (email, tipo).
     */
    public function up(): void
    {
        // Drop existing table and recreate with composite key
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email');
            $table->string('tipo')->default('usuario'); // usuario, bodega, proveedor
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['email', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        // Recreate original table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
