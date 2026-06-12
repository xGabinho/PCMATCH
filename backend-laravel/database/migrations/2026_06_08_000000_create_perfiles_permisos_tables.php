<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('perfiles')) {
            Schema::create('perfiles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 100)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('perfil_permisos')) {
            Schema::create('perfil_permisos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('perfil_id');
                $table->string('permiso', 100);
                $table->foreign('perfil_id')->references('id')->on('perfiles')->onDelete('cascade');
                $table->unique(['perfil_id', 'permiso'], 'unique_perfil_permiso');
            });
        }

        if (!Schema::hasColumn('usuarios', 'perfil_id')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->unsignedInteger('perfil_id')->nullable()->after('rol');
            });
        }
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('perfil_id');
        });
        Schema::dropIfExists('perfil_permisos');
        Schema::dropIfExists('perfiles');
    }
};
