<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Idempotente: solo cambia el tipo si aún no es enum
     * (evita alterar/mangar datos cuando la base ya está migrada).
     *
     * @author Davide Rivera (Luis Castaño)
     */
    public function up(): void
    {
        $type = collect(DB::select("SHOW COLUMNS FROM `ecografias` WHERE Field = 'estado'"))->first()->Type ?? '';
        if (stripos($type, 'enum') === false) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->enum('estado', ['EN PROCESO', 'ANULADO', 'LEIDO'])->default('EN PROCESO')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            $table->tinyInteger('estado')->default(0)->change();
        });
    }
};
