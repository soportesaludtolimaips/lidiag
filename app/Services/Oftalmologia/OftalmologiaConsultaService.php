<?php

namespace App\Services\Oftalmologia;

use App\Models\Configuracion\ConfigDiagnostico;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Oftalmologia\OftalmologiaAtencion;
use App\Models\Oftalmologia\OftalmologiaConsulta;
use App\Models\Oftalmologia\OftalmologiaDiagnostico;
use App\Models\Oftalmologia\OftalmologiaIncapacidad;
use App\Models\Oftalmologia\OftalmologiaMedicamento;
use App\Models\Oftalmologia\OftalmologiaProcedimiento;
use App\Models\Oftalmologia\OftalmologiaRemision;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la gestión clínica de la consulta e historia de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Obtiene o inicializa la consulta oftalmológica ligada a una atención.
 * 2. Guarda borradores progresivos (autoguardado) sin alterar el estado de atención.
 * 3. Finaliza la consulta clínica registrando los diagnósticos CIE-10 (relacionados con config_diagnosticos).
 * 4. Actualiza el estado de la atención a 'EN_CONSULTA' o 'ATENDIDO'.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaConsultaService extends BaseService
{
    /**
     * Obtiene los datos clínicos de una consulta por ID de atención o inicializa la estructura si aún no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorAtencion(int $atencionId): array
    {
        try {
            $atencion = OftalmologiaAtencion::with([
                'sede:id,nom_sede',
                'paciente:id,num_docu,nombres,pri_ape,seg_ape,sexo,fec_naci,cel,tel,direccion',
                'medico:id,name',
                'tipoRecaudo:id,nombre,codigo,envia_sahi',
                'adminSalud:id,nom_admin',
            ])->find($atencionId);

            if (! $atencion) {
                return $this->notFound('No se encontró la atención de oftalmología.');
            }

            $consulta = OftalmologiaConsulta::with([
                'diagnosticos.diagnostico:id,cod_diagnos,nom_diagnos',
                'procedimientos.producto:id,cod_cups,nom_produc',
                'medicamentos.producto:id,cod_cups,nom_produc',
                'remision',
                'incapacidad',
            ])->where('atencion_id', $atencionId)->first();

            return $this->success([
                'atencion' => $atencion,
                'consulta' => $consulta,
            ], 'Datos clínicos de oftalmología cargados correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error cargando consulta de oftalmología', [
                'atencion_id' => $atencionId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible cargar la información clínica de la atención: '.$e->getMessage(), 500);
        }
    }

    /**
     * Guarda la consulta clínica (modo BORRADOR o FINALIZADA).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function guardarConsulta(array $data, int $medicoId, bool $finalizar = false): array
    {
        try {
            return DB::transaction(function () use ($data, $medicoId, $finalizar) {
                $atencionId = (int) $data['atencion_id'];
                $atencion = OftalmologiaAtencion::findOrFail($atencionId);

                if ($atencion->estado === 'ANULADO') {
                    return $this->error('No se puede registrar información clínica en una atención anulada.', 422);
                }

                // 1. Obtener o crear consulta
                $consulta = OftalmologiaConsulta::firstOrNew(['atencion_id' => $atencionId]);
                $consulta->medico_id = $medicoId;
                $consulta->sede_id = $atencion->sede_id;
                $consulta->paciente_id = $atencion->paciente_id;

                // Anamnesis
                $consulta->motivo_consulta = $data['motivo_consulta'] ?? null;
                $consulta->enfermedad_actual = $data['enfermedad_actual'] ?? null;
                $consulta->antecedentes_medicos = $data['antecedentes_medicos'] ?? null;
                $consulta->antecedentes_quirurgicos_oft = $data['antecedentes_quirurgicos_oft'] ?? null;
                $consulta->antecedentes_alergicos = $data['antecedentes_alergicos'] ?? null;
                $consulta->antecedentes_otros = $data['antecedentes_otros'] ?? null;
                $consulta->revision_sistemas = $data['revision_sistemas'] ?? null;

                // Examen Físico Oftalmológico
                $consulta->av_sc_od = $data['av_sc_od'] ?? null;
                $consulta->av_sc_oi = $data['av_sc_oi'] ?? null;
                $consulta->av_cc_od = $data['av_cc_od'] ?? null;
                $consulta->av_cc_oi = $data['av_cc_oi'] ?? null;
                $consulta->av_ph_od = $data['av_ph_od'] ?? null;
                $consulta->av_ph_oi = $data['av_ph_oi'] ?? null;

                $consulta->motilidad_ocular = $data['motilidad_ocular'] ?? null;
                $consulta->pupilas = $data['pupilas'] ?? null;
                $consulta->biomicroscopia_od = $data['biomicroscopia_od'] ?? null;
                $consulta->biomicroscopia_oi = $data['biomicroscopia_oi'] ?? null;
                $consulta->presion_intraocular_od = $data['presion_intraocular_od'] ?? null;
                $consulta->presion_intraocular_oi = $data['presion_intraocular_oi'] ?? null;
                $consulta->fondo_ojo_od = $data['fondo_ojo_od'] ?? null;
                $consulta->fondo_ojo_oi = $data['fondo_ojo_oi'] ?? null;
                $consulta->examen_fisico_general = $data['examen_fisico_general'] ?? null;

                // Signos vitales
                $consulta->signos_fc = $data['signos_fc'] ?? null;
                $consulta->signos_pas = $data['signos_pas'] ?? null;
                $consulta->signos_pad = $data['signos_pad'] ?? null;
                $consulta->signos_fr = $data['signos_fr'] ?? null;
                $consulta->signos_spo2 = $data['signos_spo2'] ?? null;
                $consulta->signos_peso = ! empty($data['signos_peso']) ? (float) $data['signos_peso'] : null;
                $consulta->signos_talla = ! empty($data['signos_talla']) ? (float) $data['signos_talla'] : null;
                $consulta->signos_imc = ! empty($data['signos_imc']) ? (float) $data['signos_imc'] : null;

                // Concepto y Plan
                $consulta->concepto_plan = $data['concepto_plan'] ?? null;
                $consulta->destino_paciente = ! empty($data['destino_paciente']) ? trim((string) $data['destino_paciente']) : 'Ambulatorio';

                if ($finalizar) {
                    $consulta->estado_consulta = 'FINALIZADA';
                    $consulta->fechor_finalizacion = Carbon::now();
                } else {
                    $consulta->estado_consulta = $consulta->estado_consulta === 'FINALIZADA' ? 'FINALIZADA' : 'BORRADOR';
                }

                $consulta->save();

                // 2. Sincronizar Diagnósticos CIE-10 si se envían
                if (isset($data['diagnosticos']) && is_array($data['diagnosticos'])) {
                    $this->sincronizarDiagnosticos($consulta->id, $data['diagnosticos']);
                }

                // 3. Sincronizar Procedimientos CUPS si se envían
                if (isset($data['procedimientos']) && is_array($data['procedimientos'])) {
                    $this->sincronizarProcedimientos($consulta->id, $data['procedimientos']);
                }

                // 4. Sincronizar Medicamentos si se envían
                if (isset($data['medicamentos']) && is_array($data['medicamentos'])) {
                    $this->sincronizarMedicamentos($consulta->id, $data['medicamentos']);
                }

                // 5. Sincronizar Remisión
                if (isset($data['remision']) && is_array($data['remision'])) {
                    $this->sincronizarRemision($consulta->id, $data['remision']);
                }

                // 6. Sincronizar Incapacidad
                if (isset($data['incapacidad']) && is_array($data['incapacidad'])) {
                    $this->sincronizarIncapacidad($consulta->id, $data['incapacidad']);
                }

                // 7. Actualizar estado de la Atención
                if ($finalizar) {
                    $atencion->update([
                        'estado' => 'ATENDIDO',
                        'medico_id' => $medicoId,
                    ]);
                } elseif ($atencion->estado === 'PENDIENTE') {
                    $atencion->update([
                        'estado' => 'EN_CONSULTA',
                        'medico_id' => $medicoId,
                    ]);
                }

                $msg = $finalizar ? 'Consulta clínica de oftalmología finalizada con éxito.' : 'Borrador clínico guardado correctamente.';

                return $this->success($consulta->load(['diagnosticos.diagnostico']), $msg);
            });
        } catch (\Throwable $e) {
            Log::error('Error guardando consulta de oftalmología', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al guardar la información clínica: '.$e->getMessage(), 500);
        }
    }

    /**
     * Sincroniza la lista de diagnósticos CIE-10 para la consulta.
     *
     * @param  array<int, mixed>  $diagnosticos
     */
    protected function sincronizarDiagnosticos(int $consultaId, array $diagnosticos): void
    {
        // Purgar diagnósticos previos y recrear para mantener integridad referencial limpia
        OftalmologiaDiagnostico::where('consulta_id', $consultaId)->delete();

        foreach ($diagnosticos as $idx => $d) {
            $diagId = ! empty($d['diagnostico_id']) ? (int) $d['diagnostico_id'] : null;
            if (! $diagId) {
                // Resolver por código CIE-10 si no viene el ID directo
                $cod = trim((string) ($d['cod_cie10'] ?? ''));
                if ($cod !== '') {
                    $cfgDiag = ConfigDiagnostico::where('cod_diagnos', $cod)->first();
                    $diagId = $cfgDiag?->id;
                }
            }

            if (! $diagId) {
                continue;
            }

            $cfgDiag = ConfigDiagnostico::find($diagId);
            if (! $cfgDiag) {
                continue;
            }

            OftalmologiaDiagnostico::create([
                'consulta_id' => $consultaId,
                'diagnostico_id' => $diagId,
                'cod_cie10' => $cfgDiag->cod_diagnos,
                'nombre_diagnostico' => $cfgDiag->nom_diagnos,
                'es_principal' => (bool) ($d['es_principal'] ?? ($idx === 0)),
                'tipo_diagnostico' => in_array($d['tipo_diagnostico'] ?? '', ['IMPRESION_DIAGNOSTICA', 'CONFIRMADO_NUEVO', 'CONFIRMADO_REPETIDO'], true)
                    ? $d['tipo_diagnostico']
                    : 'CONFIRMADO_NUEVO',
                'lateralidad' => in_array($d['lateralidad'] ?? '', ['NO_APLICA', 'OJO_DERECHO', 'OJO_IZQUIERDO', 'BILATERAL'], true)
                    ? $d['lateralidad']
                    : 'NO_APLICA',
                'observaciones' => ! empty($d['observaciones']) ? substr(trim((string) $d['observaciones']), 0, 255) : null,
            ]);
        }
    }

    /**
     * Sincroniza las órdenes de procedimientos CUPS.
     *
     * @param  array<int, mixed>  $procedimientos
     */
    protected function sincronizarProcedimientos(int $consultaId, array $procedimientos): void
    {
        OftalmologiaProcedimiento::where('consulta_id', $consultaId)->delete();

        foreach ($procedimientos as $p) {
            $productoId = (int) ($p['producto_id'] ?? 0);
            $producto = ConfigProducto::find($productoId);
            if (! $producto) {
                continue;
            }

            OftalmologiaProcedimiento::create([
                'consulta_id' => $consultaId,
                'producto_id' => $productoId,
                'cod_cups' => $producto->cod_cups,
                'nombre_procedimiento' => $producto->nom_produc,
                'cantidad' => max(1, (int) ($p['cantidad'] ?? 1)),
                'justificacion_clinica' => $p['justificacion_clinica'] ?? null,
                'observaciones' => $p['observaciones'] ?? null,
            ]);
        }
    }

    /**
     * Sincroniza la formulación de medicamentos.
     *
     * @param  array<int, mixed>  $medicamentos
     */
    protected function sincronizarMedicamentos(int $consultaId, array $medicamentos): void
    {
        OftalmologiaMedicamento::where('consulta_id', $consultaId)->delete();

        foreach ($medicamentos as $m) {
            $productoId = (int) ($m['producto_id'] ?? 0);
            $producto = ConfigProducto::find($productoId);
            if (! $producto) {
                continue;
            }

            OftalmologiaMedicamento::create([
                'consulta_id' => $consultaId,
                'producto_id' => $productoId,
                'codigo_medicamento' => $producto->cod_cups,
                'nombre_medicamento' => $producto->nom_produc,
                'forma_farmaceutica' => $m['forma_farmaceutica'] ?? 'Gotas oftálmicas',
                'dosis' => $m['dosis'] ?? '1 gota',
                'via_administracion' => $m['via_administracion'] ?? 'Oftálmica',
                'frecuencia_horario' => $m['frecuencia_horario'] ?? 'Cada 8 horas',
                'duracion_tratamiento' => $m['duracion_tratamiento'] ?? '15 días',
                'cantidad_entregar' => max(1, (int) ($m['cantidad_entregar'] ?? 1)),
                'indicaciones' => $m['indicaciones'] ?? null,
            ]);
        }
    }

    /**
     * Sincroniza la remisión médica.
     *
     * @param  array<string, mixed>  $remision
     */
    protected function sincronizarRemision(int $consultaId, array $remision): void
    {
        if (empty($remision['institucion_receptora']) && empty($remision['especialidad_solicitada']) && empty($remision['resumen_clinico'])) {
            OftalmologiaRemision::where('consulta_id', $consultaId)->delete();

            return;
        }

        OftalmologiaRemision::updateOrCreate(
            ['consulta_id' => $consultaId],
            [
                'institucion_receptora' => substr(trim((string) ($remision['institucion_receptora'] ?? '')), 0, 200),
                'especialidad_solicitada' => substr(trim((string) ($remision['especialidad_solicitada'] ?? '')), 0, 150),
                'motivo_remision' => substr(trim((string) ($remision['motivo_remision'] ?? '')), 0, 255),
                'resumen_clinico' => $remision['resumen_clinico'] ?? null,
                'observaciones' => $remision['observaciones'] ?? null,
            ]
        );
    }

    /**
     * Sincroniza la incapacidad médica.
     *
     * @param  array<string, mixed>  $incapacidad
     */
    protected function sincronizarIncapacidad(int $consultaId, array $incapacidad): void
    {
        $dias = (int) ($incapacidad['dias_incapacidad'] ?? 0);
        if ($dias <= 0 || empty($incapacidad['fec_inicio'])) {
            OftalmologiaIncapacidad::where('consulta_id', $consultaId)->delete();

            return;
        }

        $fecInicio = Carbon::parse($incapacidad['fec_inicio'])->toDateString();
        $fecFin = ! empty($incapacidad['fec_fin'])
            ? Carbon::parse($incapacidad['fec_fin'])->toDateString()
            : Carbon::parse($incapacidad['fec_inicio'])->addDays($dias - 1)->toDateString();

        OftalmologiaIncapacidad::updateOrCreate(
            ['consulta_id' => $consultaId],
            [
                'fec_inicio' => $fecInicio,
                'fec_fin' => $fecFin,
                'dias_incapacidad' => $dias,
                'tipo_incapacidad' => substr(trim((string) ($incapacidad['tipo_incapacidad'] ?? 'Ambulatoria')), 0, 100),
                'origen_incapacidad' => substr(trim((string) ($incapacidad['origen_incapacidad'] ?? 'Enfermedad General')), 0, 100),
                'empresa' => substr(trim((string) ($incapacidad['empresa'] ?? '')), 0, 150) ?: null,
                'ocupacion' => substr(trim((string) ($incapacidad['ocupacion'] ?? '')), 0, 150) ?: null,
                'telefono' => substr(trim((string) ($incapacidad['telefono'] ?? '')), 0, 50) ?: null,
                'es_prorroga' => (bool) ($incapacidad['es_prorroga'] ?? false),
                'observaciones' => $incapacidad['observaciones'] ?? null,
            ]
        );
    }
}
