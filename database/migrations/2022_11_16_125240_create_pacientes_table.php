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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('num_docu', 15);
            $table->string('nombres', 50);
            $table->string('direccion', 70)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('sexo', 1)->nullable();
            $table->date('fec_naci')->nullable();
            $table->string('tel', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pacientes');
    }
};
