<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecografias', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('ecografias_productos', fn (Blueprint $t) => $t->softDeletes());
    }

    public function down(): void
    {
        Schema::table('ecografias', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('ecografias_productos', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
