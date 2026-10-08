<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de reservas (locks) de lectura y asignación de estudios.
     *
     * Un lock por (tipo, clave) impide que dos usuarios abran el mismo
     * registro a la vez; expira automáticamente a los `hasta` minutos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function up(): void
    {
        Schema::create('estudio_locks', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('clave', 100);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('hasta');
            $table->timestamps();

            $table->unique(['tipo', 'clave']);
            $table->index('hasta');
        });
    }

    /**
     * Elimina la tabla de reservas de lectura y asignación.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function down(): void
    {
        Schema::dropIfExists('estudio_locks');
    }
};
