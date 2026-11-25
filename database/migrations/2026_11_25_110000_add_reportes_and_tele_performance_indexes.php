<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega índices de alto rendimiento para el módulo de reportes, tele-radiología y conciliación PACS.
     */
    public function up(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            if (! $this->hasIndex('estudios', 'idx_estudios_study_iuid')) {
                $table->index('study_iuid', 'idx_estudios_study_iuid');
            }
            if (! $this->hasIndex('estudios', 'idx_estudios_fec_estudio')) {
                $table->index('fec_estudio', 'idx_estudios_fec_estudio');
            }
            if (! $this->hasIndex('estudios', 'idx_estudios_destino_created')) {
                $table->index(['destino_estudio', 'created_at'], 'idx_estudios_destino_created');
            }
            if (! $this->hasIndex('estudios', 'idx_estudios_mods_in_study')) {
                $table->index('mods_in_study', 'idx_estudios_mods_in_study');
            }
        });

        Schema::table('estudios_productos', function (Blueprint $table) {
            if (! $this->hasIndex('estudios_productos', 'idx_ep_activo_lectura')) {
                $table->index(['activo', 'fechor_lectura'], 'idx_ep_activo_lectura');
            }
            if (! $this->hasIndex('estudios_productos', 'idx_ep_estudio_cups')) {
                $table->index(['estudio_id', 'cod_cups'], 'idx_ep_estudio_cups');
            }
        });

        Schema::table('pacientes', function (Blueprint $table) {
            if (! $this->hasIndex('pacientes', 'idx_pacientes_num_docu')) {
                $table->index('num_docu', 'idx_pacientes_num_docu');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            if ($this->hasIndex('estudios', 'idx_estudios_study_iuid')) {
                $table->dropIndex('idx_estudios_study_iuid');
            }
            if ($this->hasIndex('estudios', 'idx_estudios_fec_estudio')) {
                $table->dropIndex('idx_estudios_fec_estudio');
            }
            if ($this->hasIndex('estudios', 'idx_estudios_destino_created')) {
                $table->dropIndex('idx_estudios_destino_created');
            }
            if ($this->hasIndex('estudios', 'idx_estudios_mods_in_study')) {
                $table->dropIndex('idx_estudios_mods_in_study');
            }
        });

        Schema::table('estudios_productos', function (Blueprint $table) {
            if ($this->hasIndex('estudios_productos', 'idx_ep_activo_lectura')) {
                $table->dropIndex('idx_ep_activo_lectura');
            }
            if ($this->hasIndex('estudios_productos', 'idx_ep_estudio_cups')) {
                $table->dropIndex('idx_ep_estudio_cups');
            }
        });

        Schema::table('pacientes', function (Blueprint $table) {
            if ($this->hasIndex('pacientes', 'idx_pacientes_num_docu')) {
                $table->dropIndex('idx_pacientes_num_docu');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();
        $result = $conn->select(
            "SELECT COUNT(1) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$dbName, $table, $indexName]
        );

        return ! empty($result) && $result[0]->cnt > 0;
    }
};
