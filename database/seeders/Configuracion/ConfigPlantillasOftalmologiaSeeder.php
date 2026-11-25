<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigPlantillaOftalmologia;
use Illuminate\Database\Seeder;

/**
 * Seeder para las plantillas clínicas de Oftalmología en el módulo de Configuración.
 *
 * Flujo del proceso:
 * 1. Lee las plantillas institucionales predefinidas extraídas de las prácticas de los especialistas en el Espinal.
 * 2. Inserta o actualiza de manera idempotente cada plantilla (Anamnesis, Examen Físico, Concepto/Plan) en config_plantillas_oftalmologia.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-20
 */
class ConfigPlantillasOftalmologiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plantillas = [
            // ==========================================
            // EXAMEN FÍSICO (Plantillas completas estructuradas en JSON)
            // ==========================================
            [
                "titulo" => "Examen Físico Estándar - Dr. Durán",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/",
                    "av_sc_oi" => "20/",
                    "motilidad_ocular" => "Ortoforia, ducciones y versiones normales.",
                    "pupilas" => "Isocóricas, normorreactivas a la luz.",
                    "biomicroscopia_od" => "Párpados con tilosis, conjuntiva tranquila, córnea transparente, cámara formada grado IV, iris sano reactivo, pupila reactiva, cristalino transparente. Fluoresceína: no hay tinción.",
                    "biomicroscopia_oi" => "Párpados con tilosis, conjuntiva tranquila, córnea transparente, cámara formada grado IV, iris sano reactivo, pupila reactiva, cristalino transparente. Fluoresceína: no hay tinción.",
                    "presion_intraocular_od" => "11 mmHg",
                    "presion_intraocular_oi" => "11 mmHg",
                    "fondo_ojo_od" => "Medios claros, retina aplicada, disco de bordes definidos con buen color, exc 0.3. Mácula sana con brillo foveolar. Vasos sanos.",
                    "fondo_ojo_oi" => "Medios claros, retina aplicada, disco de bordes definidos con buen color, exc 0.3. Mácula sana con brillo foveolar. Vasos sanos.",
                    "examen_fisico_general" => "Segmento anterior y posterior evaluados bajo biomicroscopía y oftalmoscopía.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Examen Físico Estándar - Dr. Daza",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/",
                    "av_sc_oi" => "20/",
                    "motilidad_ocular" => "Ortoforia, ducciones y versiones normales.",
                    "pupilas" => "Redondas, reactivas a la luz.",
                    "biomicroscopia_od" => "Párpados sanos, conjuntiva tranquila, córnea transparente, cámara anterior amplia, iris sano reactivo, pupila redonda, cristalino transparente. Gonioscopia ángulo abierto. Fluoresceína: no hay tinción.",
                    "biomicroscopia_oi" => "Párpados sanos, conjuntiva tranquila, córnea transparente, cámara anterior amplia, iris sano reactivo, pupila redonda, cristalino transparente. Gonioscopia ángulo abierto. Fluoresceína: no hay tinción.",
                    "presion_intraocular_od" => "14 mmHg",
                    "presion_intraocular_oi" => "14 mmHg",
                    "fondo_ojo_od" => "Medios claros, retina aplicada, disco de bordes definidos con buen color, exc 0.1. Mácula sana con brillo foveolar.",
                    "fondo_ojo_oi" => "Medios claros, retina aplicada, disco de bordes definidos con buen color, exc 0.1. Mácula sana con brillo foveolar.",
                    "examen_fisico_general" => "Sin alteraciones agudas en anexos ni segmento anterior.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Examen Físico Estándar - Dra. Riaño (Gerontoxon)",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/",
                    "av_sc_oi" => "20/",
                    "motilidad_ocular" => "Ortoforia, movimientos oculares conservados.",
                    "pupilas" => "Redondas reactivas.",
                    "biomicroscopia_od" => "Párpados sanos, conjuntiva tranquila, córnea clara con gerontoxon, cámara anterior formada amplia, iris sano, pupila redonda reactiva, cristalino claro.",
                    "biomicroscopia_oi" => "Párpados sanos, conjuntiva tranquila, córnea clara con gerontoxon, cámara anterior formada amplia, iris sano, pupila redonda reactiva, cristalino claro.",
                    "presion_intraocular_od" => "13 mmHg",
                    "presion_intraocular_oi" => "13 mmHg",
                    "fondo_ojo_od" => "Fondo sin dilatación: disco de bordes definidos con buen color, exc 0.3. Mácula sana con brillo foveolar.",
                    "fondo_ojo_oi" => "Fondo sin dilatación: disco de bordes definidos con buen color, exc 0.3. Mácula sana con brillo foveolar.",
                    "examen_fisico_general" => "Examen oftalmológico sin dilatación pupilar.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Examen Físico Estándar - Dra. Escobar",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/",
                    "av_sc_oi" => "20/",
                    "motilidad_ocular" => "Ducciones y versiones completas sin dolor.",
                    "pupilas" => "Normorreactivas, simétricas.",
                    "biomicroscopia_od" => "Segmento anterior: córnea clara, cámara anterior formada central 3mm, periférica amplia, Tyndall y células negativo, iris sin lesiones, cristalino transparente, BUT de 12 seg, tinción con fluoresceína negativo, conjuntiva sana sin lesiones.",
                    "biomicroscopia_oi" => "Segmento anterior: córnea clara, cámara anterior formada central 3mm, periférica amplia, Tyndall y células negativo, iris sin lesiones, cristalino transparente, BUT de 12 seg, tinción con fluoresceína negativo, conjuntiva sana sin lesiones.",
                    "presion_intraocular_od" => "12 mmHg",
                    "presion_intraocular_oi" => "12 mmHg",
                    "fondo_ojo_od" => "Disco de bordes definidos, excavación 20%, mácula con brillo foveal conservado, retina aplicada sin lesiones. Vasos sin alteración.",
                    "fondo_ojo_oi" => "Disco de bordes definidos, excavación 20%, mácula con brillo foveal conservado, retina aplicada sin lesiones. Vasos sin alteración.",
                    "examen_fisico_general" => "Superficie ocular íntegra con BUT normal y prueba de fluoresceína negativa.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Examen Físico Estándar - Dr. Miranda",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/20",
                    "av_sc_oi" => "20/20",
                    "motilidad_ocular" => "Ortoforia. Ducciones y versiones normales.",
                    "pupilas" => "Isocóricas, normorreactivas.",
                    "biomicroscopia_od" => "Córnea clara, accesos angulares amplios, conjuntivitis leve.",
                    "biomicroscopia_oi" => "Córnea clara, accesos angulares amplios, conjuntivitis leve.",
                    "presion_intraocular_od" => "Tono digital normal - 14 mmHg",
                    "presion_intraocular_oi" => "Tono digital normal - 14 mmHg",
                    "fondo_ojo_od" => "Retinas aplicadas, máculas de aspecto normal, papilas simétricas, anillo neural normal, excavación E/D 0.2/0.3.",
                    "fondo_ojo_oi" => "Retinas aplicadas, máculas de aspecto normal, papilas simétricas, anillo neural normal, excavación E/D 0.2/0.3.",
                    "examen_fisico_general" => "Externo: Párpados sin alteraciones, cejas y anexos sin alteraciones.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Examen Físico Estándar - Dr. Acevedo",
                "seccion" => "EXAMEN_FISICO",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "av_sc_od" => "20/",
                    "av_sc_oi" => "20/",
                    "motilidad_ocular" => "Movimientos oculares conservados, ortoforia.",
                    "pupilas" => "Redondas, centrales, reactivas a la luz.",
                    "biomicroscopia_od" => "Córnea transparente, cámara anterior formada, VH 3, pupila redonda central reactiva a la luz, cristalino transparente.",
                    "biomicroscopia_oi" => "Córnea transparente, cámara anterior formada, VH 3, pupila redonda central reactiva a la luz, cristalino transparente.",
                    "presion_intraocular_od" => "12 mmHg",
                    "presion_intraocular_oi" => "12 mmHg",
                    "fondo_ojo_od" => "Disco óptico rosado, excavación vertical 0.3, brillo macular conservado, EPR sano, retina periférica sin lesiones.",
                    "fondo_ojo_oi" => "Disco óptico rosado, excavación vertical 0.3, brillo macular conservado, EPR sano, retina periférica sin lesiones.",
                    "examen_fisico_general" => "Se explican hallazgos diagnósticos y conducta al paciente.",
                ], JSON_UNESCAPED_UNICODE),
            ],

            // ==========================================
            // ANAMNESIS (Plantillas de motivos y antecedentes)
            // ==========================================
            [
                "titulo" => "Anamnesis Primera Vez - Con Acompañante",
                "seccion" => "ANAMNESIS",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "motivo_consulta" => "Paciente asiste con acompañante a consulta de primera vez con especialista en oftalmología.",
                    "enfermedad_actual" => "Paciente refiere disminución progresiva de agudeza visual. Niega dolor ocular intenso, fosfenos o miodesopsias agudas.",
                    "antecedentes_medicos" => "Niega enfermedades sistémicas crónicas conocidas.",
                    "antecedentes_quirurgicos_oft" => "Niega cirugías oculares previas.",
                    "antecedentes_alergicos" => "Niega alergias medicamentosas conocidas.",
                    "antecedentes_otros" => "Familiares: niega glaucoma o ceguera temprana.",
                    "revision_sistemas" => "Sin sintomatología respiratoria, cardiovascular o neurológica asociada.",
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                "titulo" => "Anamnesis - Disminución Agudeza Visual (Dr. Acevedo)",
                "seccion" => "ANAMNESIS",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => json_encode([
                    "motivo_consulta" => "Disminución de la agudeza visual mayor ojo derecho.",
                    "enfermedad_actual" => "Paciente asiste con acompañante a consulta de primera vez con especialista en oftalmología por cuadro de disminución de la agudeza visual mayor en ojo derecho de instauración insidiosa.",
                    "antecedentes_medicos" => "Niega.",
                    "antecedentes_quirurgicos_oft" => "Niega.",
                    "antecedentes_alergicos" => "Niega alergias.",
                    "antecedentes_otros" => "Sin antecedentes familiares patológicos de importancia.",
                    "revision_sistemas" => "Refiere cefalea ocasional relacionada con esfuerzo visual.",
                ], JSON_UNESCAPED_UNICODE),
            ],

            // ==========================================
            // CONCEPTO Y PLAN
            // ==========================================
            [
                "titulo" => "Concepto y Plan - Explicación Hallazgos y Control",
                "seccion" => "CONCEPTO_PLAN",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => "Se explican hallazgos diagnósticos y conducta al paciente y acompañante. Se formulan gafas / corrección óptica si aplica. Control periódico por oftalmología según evolución. Signos de alarma explicados: dolor ocular súbito, pérdida brusca de visión o destellos lumínicos consultar por urgencias.",
            ],
            [
                "titulo" => "Concepto y Plan - Superficie Ocular / Lubricación",
                "seccion" => "CONCEPTO_PLAN",
                "medico_id" => null,
                "sede_id" => null,
                "activo" => true,
                "contenido" => "Paciente con cuadro compatible con disfunción de película lagrimal / ojo seco. Se indica lubricación ocular con lágrimas artificiales sin preservantes cada 6 horas. Higiene palpebral con compresas tibias. Se explican signos de alarma y se programa control según criterio.",
            ],
        ];

        foreach ($plantillas as $item) {
            ConfigPlantillaOftalmologia::updateOrCreate(
                [
                    "titulo" => $item["titulo"],
                    "seccion" => $item["seccion"],
                ],
                [
                    "medico_id" => $item["medico_id"],
                    "sede_id" => $item["sede_id"],
                    "contenido" => $item["contenido"],
                    "activo" => $item["activo"],
                ]
            );
        }
    }
}
