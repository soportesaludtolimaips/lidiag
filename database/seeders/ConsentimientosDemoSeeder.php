<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\ConsentimientoPlantillaProducto;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para el módulo de Consentimientos Informados:
 * pacientes de ejemplo, una plantilla con placeholders y su asignación
 * a productos reales (para que "Generar" encuentre plantilla).
 *
 * Idempotente: no duplica si ya existe.
 */
class ConsentimientosDemoSeeder extends Seeder
{
    private const MODULO_RX = 1;

    public function run(): void
    {
        $this->crearPacientes();
        $this->crearPlantillaYAsignaciones();
        $this->configurarSedeDemo();
    }

    /**
     * Configura logo + info de la primera sede para el encabezado de los PDF.
     */
    private function configurarSedeDemo(): void
    {
        $sede = ConfigSede::query()->first();
        if (! $sede || $sede->logo_1) {
            return;
        }

        $destino = public_path('logos/salud_tolima.png');
        if (! is_file($destino)) {
            @mkdir(public_path('logos'), 0755, true);
            $src = public_path('admin-wrap/assets/images/logo-text.png');
            if (is_file($src)) {
                @copy($src, $destino);
            }
        }

        $sede->logo_1 = 'salud_tolima.png';
        $sede->encabezado = "IPS SALUD TOLIMA S.A.S.\nCra 5 # 10-20, Espinal - Tolima\nTel: (608) 248 0000  ·  NIT: 900.123.456-7";
        $sede->pie_pagina = 'Documento generado electronicamente - IPS Salud Tolima S.A.S.';
        $sede->save();
    }

    private function crearPacientes(): void
    {
        $pacientes = [
            ['num_docu' => '111222333', 'nombres' => 'JUAN CARLOS', 'seg_nom' => '', 'pri_ape' => 'PEREZ', 'seg_ape' => 'GOMEZ', 'sexo' => 'M', 'fec_naci' => '1985-04-12', 'cel' => '3001112233', 'email' => 'juan.perez@example.com', 'direccion' => 'Cra 1 # 2-3'],
            ['num_docu' => '444555666', 'nombres' => 'MARIA', 'seg_nom' => 'FERNANDA', 'pri_ape' => 'LOPEZ', 'seg_ape' => 'DIAZ', 'sexo' => 'F', 'fec_naci' => '1990-09-25', 'cel' => '3004445566', 'email' => 'maria.lopez@example.com', 'direccion' => 'Calle 10 # 5-20'],
            ['num_docu' => '777888999', 'nombres' => 'PEDRO', 'seg_nom' => '', 'pri_ape' => 'RAMIREZ', 'seg_ape' => 'TORRES', 'sexo' => 'M', 'fec_naci' => '1978-01-30', 'cel' => '3007778899', 'email' => 'pedro.ramirez@example.com', 'direccion' => 'Av. Siempre Viva 742'],
        ];

        foreach ($pacientes as $p) {
            Paciente::firstOrCreate(
                ['num_docu' => $p['num_docu']],
                array_merge($p, ['tipo_documento_id' => 1]) // 1 = Cédula de Ciudadanía
            );
        }
    }

    private function crearPlantillaYAsignaciones(): void
    {
        $usuarioId = (int) (User::query()->value('id') ?? 1);

        // Primeros productos reales para asignar la plantilla
        $productos = ConfigProducto::query()->orderBy('id')->limit(3)->pluck('id')->all();
        if (empty($productos)) {
            return;
        }
        $primerProducto = (int) $productos[0];

        $plantilla = ConsentimientoPlantilla::firstOrCreate(
            ['config_modulo_id' => self::MODULO_RX, 'producto_id' => $primerProducto, 'vigente' => true],
            [
                'usuario_crea_id' => $usuarioId,
                'nombre' => 'Consentimiento Informado - Demo',
                'html' => $this->html(),
                'version' => 1,
                'vigente_key' => 1,
            ]
        );

        foreach ($productos as $productoId) {
            ConsentimientoPlantillaProducto::firstOrCreate(
                ['plantilla_id' => $plantilla->id, 'producto_id' => (int) $productoId, 'sede_id' => null],
                ['requiere_firma_medico' => true]
            );
        }
    }

    private function html(): string
    {
        return <<<'HTML'
<h3 style="text-align:center;">CONSENTIMIENTO INFORMADO</h3>
<p>Yo, <strong>${nombres} ${apellidos}</strong>, identificado(a) con documento
No. <strong>${num_docu}</strong>, en pleno uso de mis facultades, declaro que se
me ha explicado de forma clara el procedimiento <strong>${producto}</strong>,
sus beneficios, riesgos y alternativas.</p>

<p>Autorizo la realización del procedimiento el día <strong>${fecha}</strong> a
las <strong>${hora}</strong>, y manifiesto haber resuelto todas mis dudas.</p>

<p>Observaciones: ${observaciones}</p>

<div style="margin-top:110px;"></div>
<table style="width:100%; text-align:center;">
  <tr>
    <td style="width:50%; padding:0 20px;">
      ${firma_paciente}
      <div style="border-top:1px solid #333; margin-top:6px; padding-top:4px;">Firma del paciente/acompañante</div>
    </td>
    <td style="width:50%; padding:0 20px;">
      ${firma_medico}
      <div style="border-top:1px solid #333; margin-top:6px; padding-top:4px;">Firma del médico (${nombre_medico})</div>
    </td>
  </tr>
</table>
HTML;
    }
}
