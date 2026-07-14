<?php

namespace App\Services\Interfaces;

use App\Http\Controllers\Dcm4chee\StudyController;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioProductoAdendum;
use App\Models\General\Paciente;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeleRadiologiaService extends BaseService
{
    public function listarEstudios($request): array
    {
        return $this->success(app(StudyController::class)->listarEstudios($request, new Study));
    }

    public function enviarEstudio(): array
    {
        return $this->error('Endpoint no implementado. Use /tele-radiologia-enviar-orden y /estudios.', 501);
    }

    public function enviarOrdenTeleRadiologia(array $orden): array
    {
        try {
            $url = config('services.teleradiologia.orden_url');

            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
                ->connectTimeout((int) config('services.teleradiologia.connect_timeout'))
                ->timeout((int) config('services.teleradiologia.timeout'))
                ->retry((int) config('services.teleradiologia.retries'), 200)
                ->acceptJson()
                ->post($url, $orden);

            if ($response->successful()) {
                $json = $response->json();

                $confirmation = null;

                if (is_array($json)) {
                    $confirmation = $json['confirmation']
                        ?? $json['confirmation_id']
                        ?? $json['id']
                        ?? null;

                    if (
                        $confirmation === null &&
                        isset($json['data']) &&
                        is_array($json['data'])
                    ) {
                        $confirmation = $json['data']['confirmation']
                            ?? $json['data']['id']
                            ?? null;
                    }
                }

                return $this->success([
                    'message' => $json['message'] ?? 'Orden enviada correctamente.',
                    'confirmation' => $confirmation,
                    'data' => $json,
                ], $json['message'] ?? 'Orden enviada correctamente.', $response->status());
            }

            return $this->error(
                'Error en la API externa',
                $response->status(),
                $response->json() ?? $response->body()
            );
        } catch (\Throwable $e) {
            return $this->error('Error interno en el servidor proxy', 500, $e->getMessage());
        }
    }

    public function recibirLectura(array $data): array
    {
        try {
            $d = $data;

            $medicoData = (isset($d['adendum']['medico']))
                ? $d['adendum']['medico']
                : $d['medico'];

            $medico = $this->procesarMedicoConFirma($medicoData);

            $estudio = Estudio::where('study_iuid', $d['study_iuid'])->first();

            if (! $estudio) {
                return $this->notFound('Estudio no encontrado con IUID: '.$d['study_iuid']);
            }

            $estudioProducto = EstudioProducto::where('estudio_id', $estudio->id)
                ->where('cod_cups', $d['cod_cups'])
                ->first();

            if (! $estudioProducto) {
                return $this->notFound('Producto CUPS no encontrado en este estudio.');
            }

            if (isset($d['adendum'])) {
                $adendum = EstudioProductoAdendum::create([
                    'medico_id' => $medico->id,
                    'estudio_producto_id' => $estudioProducto->id,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? now(),
                    'lectura' => $d['adendum']['lectura'] ?? '',
                    'activo' => 1,
                ]);

                $estudioProducto->update([
                    'lectura' => $d['adendum']['lectura'] ?? null,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? $d['fechor_lectura'] ?? null,
                ]);

                return $this->success([
                    'estudio_id' => $estudio->id,
                    'adendum_id' => $adendum->id,
                ], 'Lectura y adendum procesados correctamente.');
            }

            $sexo = strtoupper(substr((string) ($d['paciente']['sexo'] ?? 'M'), 0, 1));
            Paciente::updateOrCreate(
                ['num_docu' => $d['paciente']['num_docu']],
                [
                    'tipo_documento_id' => 1,
                    'nombres' => $d['paciente']['nom_pacien'] ?? null,
                    'sexo' => in_array($sexo, ['M', 'F']) ? $sexo : 'M',
                ]
            );

            if (! $estudio->medico_id) {
                $estudio->update(['medico_id' => $medico->id]);
            }

            $estudioProducto->update([
                'fechor_lectura' => $d['fechor_lectura'] ?? $d['fecha_lectura'] ?? null,
                'lectura' => $d['lectura'] ?? null,
            ]);

            return $this->success([
                'estudio_id' => $estudio->id,
                'medico' => $medico->name,
            ], 'Lectura procesada correctamente.');
        } catch (\Exception $e) {
            Log::error("Error crítico en recibirLectura: ".$e->getMessage());

            return $this->error('Error interno del servidor.', 500, $e->getMessage());
        }
    }

    private function procesarMedicoConFirma(array $medicoData)
    {
        $medico = User::firstOrNew(['reg_med' => $medicoData['reg_medico']]);

        $medico->name = 'TL '.($medicoData['name'] ?? 'Sin Nombre');
        $medico->reg_med = $medicoData['reg_medico'] ?? $medico->reg_med;

        if (! $medico->exists) {
            $medico->email = $medicoData['num_docu'].'@teleradiologia.com';
            $medico->password = bcrypt('password');
        }

        $firmaRaw = $medicoData['firma_medico'] ?? null;

        if ($firmaRaw && strlen($firmaRaw) > 100) {
            try {
                $extension = 'png';
                if (preg_match('/^data:image\/(\w+);base64,/', $firmaRaw, $type)) {
                    $extension = strtolower($type[1]);
                    $firmaRaw = substr($firmaRaw, strpos($firmaRaw, ',') + 1);
                }

                $imgData = base64_decode($firmaRaw);

                if ($imgData) {
                    if ($medico->firma && Storage::disk('firmas_medicos')->exists($medico->firma)) {
                        Storage::disk('firmas_medicos')->delete($medico->firma);
                    }

                    $nombreArchivo = Str::random(50).'.'.$extension;

                    $guardadoExitoso = Storage::disk('firmas_medicos')->put($nombreArchivo, $imgData);

                    if ($guardadoExitoso) {
                        $medico->firma = 'firmas_medicos/'.$nombreArchivo;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al sustituir firma del médico {$medico->num_docu}: ".$e->getMessage());
            }
        }

        $medico->save();

        return $medico;
    }
}
