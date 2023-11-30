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
        Schema::create('estudios_audios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('estudio_id');
            $table->foreign('estudio_id')->references('id')->on('estudios');

            $table->string('audio');
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
        Schema::dropIfExists('estudios_audios');
    }
};
