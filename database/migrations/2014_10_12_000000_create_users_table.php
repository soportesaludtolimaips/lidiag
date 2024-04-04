<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('num_docu', 20)->nullable();
            $table->string('reg_med', 20)->nullable();
            $table->string('name', 50);
            $table->string('email')->unique();
            $table->string('tel', 50)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('twitter', 100)->nullable();
            $table->string('youtube', 100)->nullable();
            $table->string('interface_id')->nullable();
            $table->boolean('permitir_transcribir')->nullable()->default(); // Permiso para que el usuario este asignando lectura puede realizar la lectura
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->char('imagen_perfil', 200)->nullable();
            $table->char('firma', 200)->nullable();
            $table->boolean('estado')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
