<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use Illuminate\Database\Seeder;

class ConfigRecomendacionProductoSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar o actualizar las recomendaciones y requerimientos
     * preoperatorios y asistenciales de productos CUPS en LIDiag y el Bot de WhatsApp.
     *
     * Flujo del proceso:
     * 1. Define el catálogo canónico de recomendaciones clínicas por código CUPS y nombre de producto.
     * 2. Resuelve el producto en la tabla config_productos por su código CUPS o coincidencia de nombre.
     * 3. Registra o actualiza de manera idempotente la recomendación activa en config_recomendacion_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function run(): void
    {
        $items = [
            // ==============================================================
            // 1. Biopsias e Intervencionismo (Requerimiento de 3 órdenes y 3 autorizaciones)
            // ==============================================================
            [
                'cups' => '851102',
                'nombre_like' => 'BIOPSIA DE MAMA CON AGUJA (TRUCUT)',
                'rec' => "*REQUERIMIENTOS OBLIGATORIOS PARA BIOPSIA DE MAMA TRUCUT (CÓD. 851102):*\n\n"
                    ."Estimado usuario, verifique haber enviado el paquete completo para el procedimiento de biopsia. Se requiere obligatoriamente que sean enviadas TRES (3) ÓRDENES MÉDICAS y TRES (3) AUTORIZACIONES vigentes dirigidas a nuestra institución:\n"
                    ."1. Biopsia de Mama con aguja Trucut (Cód. 851102)\n"
                    ."2. Estudio de coloración básica en biopsia Trucut / Patología (Cód. 898101)\n"
                    ."3. Guía ecográfica para procedimiento intervencionista (Cód. 881701)\n\n"
                    ."Adicionalmente:\n"
                    ."• Adjuntar foto legible del reporte escrito de Mamografía o Ecografía previa del área a estudiar.\n"
                    ."• Informar qué medicamentos toma actualmente y si consume algún medicamento anticoagulante (Aspirina, ASA, Warfarina, etc.).",
            ],
            [
                'cups' => '851101',
                'nombre_like' => 'BIOPSIA POR PUNCIÓN CON AGUJA FINA DE MAMA',
                'rec' => "*REQUERIMIENTOS OBLIGATORIOS PARA BACAF DE MAMA (CÓD. 851101):*\n\n"
                    ."Estimado usuario, para el procedimiento BACAF se requiere el paquete completo de TRES (3) ÓRDENES MÉDICAS y TRES (3) AUTORIZACIONES vigentes:\n"
                    ."1. Biopsia por punción con aguja fina BACAF de mama (Cód. 851101)\n"
                    ."2. Estudio de coloración básica en citología por aspiración (Cód. 898003)\n"
                    ."3. Guía ecográfica (Cód. 881701)\n\n"
                    ."Adicionalmente enviar foto del reporte escrito de Mamografía o Ecografía previa e informar si consume medicamentos anticoagulantes.",
            ],
            [
                'cups' => '061002',
                'nombre_like' => 'BIOPSIA DE GLÁNDULA TIROIDES VÍA PERCUTÁNEA',
                'rec' => "*REQUERIMIENTOS OBLIGATORIOS PARA BACAF DE TIROIDES (CÓD. 061002):*\n\n"
                    ."Estimado usuario, para la toma de BACAF de tiroides se requiere paquete completo de TRES (3) ÓRDENES y TRES (3) AUTORIZACIONES vigentes:\n"
                    ."1. Biopsia de tiroides por aspiración / BACAF (Cód. 061002)\n"
                    ."2. Estudio de coloración básica en citología por aspiración de cualquier tejido (Cód. 898003)\n"
                    ."3. Guía ecográfica (Cód. 881701)\n\n"
                    ."Adicionalmente adjuntar reporte escrito de la ecografía de tiroides previa y confirmar si está tomando medicamentos anticoagulantes (Aspirina, ASA, Warfarina).",
            ],

            // ==============================================================
            // 2. Ecografías Abdominales y Digestivas
            // ==============================================================
            [
                'cups' => '881302',
                'nombre_like' => 'ECOGRAFIA DE ABDOMEN TOTAL',
                'rec' => "*PREPARACIÓN ECOGRAFÍA DE ABDOMEN TOTAL:*\n\n"
                    ."• Venir totalmente en ayunas el día de la cita.\n"
                    ."• Tomar abundante agua una (1) hora antes de la cita y NO orinar para tener la vejiga llena (ganas de orinar).\n"
                    ."• El día anterior a la cita después del mediodía comer solo caldos y jugos que no contengan leche, grasas, chocolate, avena, granos, gaseosas ni fritos (dieta líquida o blanda).\n"
                    ."• Recuerde que su cita alguien más la necesita; si no puede asistir, por favor cancélela o reprográmela oportunamente.",
            ],
            [
                'cups' => null,
                'nombre_like' => 'HIGADO PANCREAS VIA BILIAR',
                'rec' => "*PREPARACIÓN ECOGRAFÍA HEPATOBILIAR / ABDOMEN SUPERIOR:*\n\n"
                    ."• Venir totalmente en ayunas el día de la cita.\n"
                    ."• El día anterior después del mediodía solo consumir dieta líquida o blanda (caldos y jugos sin leche, grasas, chocolate ni bebidas con gas).\n"
                    ."• Si no puede asistir, favor cancelar o reprogramar oportunamente.",
            ],
            [
                'cups' => '881313',
                'nombre_like' => 'ECOGRAFIA DE ABDOMEN PILORO',
                'rec' => "*PREPARACIÓN ECOGRAFÍA DE ABDOMEN PÍLORO:*\n\n"
                    ."• Pacientes pediátricos: traer tetero o leche para administrar durante el examen según indicación médica.\n"
                    ."• Ayuno de 3 a 4 horas previas al examen.",
            ],

            // ==============================================================
            // 3. Vías Urinarias, Pélvicas y Ginecológicas
            // ==============================================================
            [
                'cups' => '881332',
                'nombre_like' => 'ECOGRAFIA DE VIAS URINARIAS',
                'rec' => "*PREPARACIÓN ECOGRAFÍA DE VÍAS URINARIAS O PÉLVICA TRANSABDOMINAL:*\n\n"
                    ."• Tomar abundante agua dos (2) horas antes de la cita y NO orinar (el examen se realiza con vejiga llena, ganas de orinar).\n"
                    ."• Si es paciente renal o en diálisis, infórmelo previamente al personal de salud.\n"
                    ."• Si tiene sonda vesical: debe pinzar o cerrar la sonda una (1) hora antes del examen y tomar agua suficiente. Si no sabe cómo pinzarla, informe a la auxiliar de enfermería al llegar a ecografías.",
            ],
            [
                'cups' => '881432',
                'nombre_like' => 'ECOGRAFIA OBSTETRICA TRANSVAGINAL',
                'rec' => "*RECOMENDACIONES ECOGRAFÍA PÉLVICA / OBSTÉTRICA TRANSVAGINAL:*\n\n"
                    ."• No requiere preparación especial; tenga o no su período menstrual se puede realizar el examen.\n"
                    ."• Si la paciente es menor de edad, debe venir obligatoriamente acompañada por padre o madre.\n"
                    ."• Favor traer dos (2) preservativos y preferiblemente venir en falda o vestido cómodo. Por favor no traer acompañantes niños.",
            ],
            [
                'cups' => null,
                'nombre_like' => 'PELVICA O GINECOLOGICA TRANSVAGINAL',
                'rec' => "*RECOMENDACIONES ECOGRAFÍA PÉLVICA O GINECOLÓGICA TRANSVAGINAL:*\n\n"
                    ."• No requiere ayuno ni preparación especial; se puede realizar con el período menstrual.\n"
                    ."• Si la paciente es menor de edad, debe acudir acompañada por padre o madre.\n"
                    ."• Favor traer dos (2) preservativos y asistir preferiblemente en falda o vestido.",
            ],

            // ==============================================================
            // 4. Próstata Transrectal
            // ==============================================================
            [
                'cups' => '881502',
                'nombre_like' => 'ECOGRAFIA DE PROSTATA TRANSRECTAL',
                'rec' => "*PREPARACIÓN Y RECOMENDACIONES ECOGRAFÍA DE PRÓSTATA TRANSRECTAL:*\n\n"
                    ."• Venir el día de la cita totalmente en ayunas.\n"
                    ."• Dieta líquida estricta dos (2) días antes: consumir únicamente caldos y jugos que no contengan leche ni huevos.\n"
                    ."• Aplicar un enema rectal comercial el día anterior a las 10:00 PM y un segundo enema el día de la cita a las 6:00 AM.\n"
                    ."• Asistir con disponibilidad de tiempo y acompañado de un adulto responsable. Si es un examen de control, traer ecografías y reportes anteriores.",
            ],

            // ==============================================================
            // 5. Procedimientos Doppler y Obstétricos Especiales
            // ==============================================================
            [
                'cups' => '882222',
                'nombre_like' => 'ARTERIAS RENALES A COLOR',
                'rec' => "*PREPARACIÓN DOPPLER DE ARTERIAS RENALES A COLOR:*\n\n"
                    ."• Venir totalmente en ayunas el día de la cita.\n"
                    ."• El día anterior a la cita después del mediodía solo consumir dieta blanda o líquida sin lácteos, grasas ni bebidas gaseosas.",
            ],
            [
                'cups' => '882296',
                'nombre_like' => 'HIPERTENSION PORTAL A COLOR',
                'rec' => "*PREPARACIÓN DOPPLER CON EVALUACIÓN DE FLUJO EN HIPERTENSIÓN PORTAL:*\n\n"
                    ."• Venir totalmente en ayunas el día de la cita.\n"
                    ."• El día anterior a la cita después del mediodía consumir únicamente dieta líquida o blanda sin lácteos ni bebidas con gas.",
            ],
            [
                'cups' => '881360',
                'nombre_like' => 'ECOGRAFIA PELVICA CON ANALISIS DOPPLER ACR',
                'rec' => "*REQUERIMIENTOS Y RECOMENDACIONES DOPPLER PÉLVICO / OBSTÉTRICO:*\n\n"
                    ."• Estimado usuario, para programar el Doppler Obstétrico requerimos que le ordenen y autoricen complementariamente la Ecografía Obstétrica Transabdominal (Cód. 881431), ya que son exámenes complementarios que se toman el mismo día con el especialista ginecobstetra perinatologo.\n"
                    ."• Puede solicitar la orden con su médico de control prenatal o especialista para programar ambas citas simultáneamente.",
            ],
            [
                'cups' => '881436',
                'nombre_like' => 'TRANSLUCENCIA NUCAL',
                'rec' => "*RECOMENDACIONES ECOGRAFÍA OBSTÉTRICA DE TRANSLUCENCIA NUCAL:*\n\n"
                    ."• Este estudio se realiza estrictamente entre la semana 11 y la semana 13 semanas y 6 días de gestación.\n"
                    ."• No requiere ayuno estricto. Traer documento de identidad, ecografía previa de fechado y carné de control prenatal.",
            ],

            // ==============================================================
            // 6. Mamografía y Radiología Convencional
            // ==============================================================
            [
                'cups' => '876802',
                'nombre_like' => 'MAMOGRAFIA BILATERAL',
                'rec' => "*RECOMENDACIONES MAMOGRAFÍA BILATERAL:*\n\n"
                    ."• No requiere ayuno ni preparación especial.\n"
                    ."• El día del examen NO aplicar desodorante, talco, loción ni cremas en axilas o senos (las partículas pueden interferir con la imagen simulando microcalcificaciones).\n"
                    ."• Vista ropa de dos piezas cómoda. Traiga estudios mamográficos y ecográficos anteriores para estudio comparativo.",
            ],
            [
                'cups' => '870001',
                'nombre_like' => 'RADIOGRAFIA DE CRANEO',
                'rec' => "*RECOMENDACIONES RADIOGRAFÍA DE CRÁNEO / CARA:*\n\n"
                    ."• No requiere ayuno ni preparación especial.\n"
                    ."• Retirar todos los objetos metálicos de la cabeza y cuello (aretes, cadenas, ganchos, prótesis dentales removibles).",
            ],
            [
                'cups' => '871121',
                'nombre_like' => 'fluoroscopia',
                'rec' => "*RECOMENDACIONES RADIOGRAFÍA DE TÓRAX:*\n\n"
                    ."• No requiere ayuno. Asista con ropa cómoda.\n"
                    ."• Pacientes femeninas en edad fértil: informar al personal de radiología si existe alguna posibilidad de embarazo.",
            ],
            [
                'cups' => '872002',
                'nombre_like' => 'RADIOGRAFIA DE ABDOMEN SIMPLE',
                'rec' => "*RECOMENDACIONES RADIOGRAFÍA DE ABDOMEN SIMPLE / TRÁNSITO:*\n\n"
                    ."• No ingerir alimentos sólidos 6 horas antes del estudio. Puede tomar líquidos claros.\n"
                    ."• No fume ni consuma goma de mascar antes del procedimiento.",
            ],
            [
                'cups' => '873121',
                'nombre_like' => 'RADIOGRAFIA DE HUMERO',
                'rec' => "*RECOMENDACIONES RADIOGRAFÍA DE MIEMBROS SUPERIORES:*\n\n"
                    ."• No requiere preparación especial. Asistir con ropa cómoda que permita descubrir fácilmente el brazo/área a examinar.",
            ],
            [
                'cups' => '873420',
                'nombre_like' => 'RADIOGRAFIA DE RODILLA',
                'rec' => "*RECOMENDACIONES RADIOGRAFÍA DE MIEMBROS INFERIORES:*\n\n"
                    ."• No requiere preparación especial. Vista ropa cómoda que permita descubrir la rodilla/pierna. Traiga estudios radiológicos anteriores si dispone de ellos.",
            ],

            // ==============================================================
            // 7. Tomografía Computarizada (TAC)
            // ==============================================================
            [
                'cups' => '879111',
                'nombre_like' => 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO',
                'rec' => "*RECOMENDACIONES TOMOGRAFÍA (TAC) DE CRÁNEO SIMPLE:*\n\n"
                    ."• No requiere ayuno para tomografía simple. Retirar objetos metálicos (aretes, ganchos, gafas, prótesis dentales).\n"
                    ."• Si el médico tratante ordenó el examen con medio de contraste, se requiere ayuno de 6 horas y examen de laboratorio reciente de Creatinina.",
            ],
            [
                'cups' => '879301',
                'nombre_like' => 'TOMOGRAFIA AXIAL COMPUTADA DE TORAX',
                'rec' => "*RECOMENDACIONES TOMOGRAFÍA (TAC) DE TÓRAX:*\n\n"
                    ."• Para estudio simple no requiere ayuno. Para estudio contrastado, requiere ayuno de 6 horas y Creatinina reciente (no mayor a 30 días).\n"
                    ."• Retirar collares, cadenas y objetos metálicos del tórax.",
            ],
            [
                'cups' => '879410',
                'nombre_like' => 'TOMOGRAFIA AXIAL COMPUTADA DE ABDOMEN',
                'rec' => "*RECOMENDACIONES TOMOGRAFÍA (TAC) DE ABDOMEN:*\n\n"
                    ."• Ayuno de 6 a 8 horas. No ingerir alimentos sólidos ni bebidas carbonatadas. Puede tomar agua.\n"
                    ."• Aportar resultado de Creatinina no mayor a 30 días si el examen requiere medio de contraste endovenoso.",
            ],

            // ==============================================================
            // 8. Resonancia Magnética (RM)
            // ==============================================================
            [
                'cups' => '883101',
                'nombre_like' => 'RESONANCIA NUCLEAR MAGNETICA',
                'rec' => "*RECOMENDACIONES RESONANCIA MAGNÉTICA (RM):*\n\n"
                    ."• Ayuno de 4 a 6 horas.\n"
                    ."• Retirar obligatoriamente TODOS los objetos metálicos (joyas, reloj, monedas, llaves, horquillas, tarjetas magnéticas).\n"
                    ."• INFORMAR OBLIGATORIAMENTE al personal si tiene marcapasos cardíaco, implante coclear, clips de aneurisma, prótesis metálicas, fragmentos de metralla o si sufre de claustrofobia.",
            ],
        ];

        foreach ($items as $item) {
            $producto = null;
            if (! empty($item['cups'])) {
                $producto = ConfigProducto::where('cod_cups', $item['cups'])->first();
            }
            if (! $producto && ! empty($item['nombre_like'])) {
                $producto = ConfigProducto::where('nom_produc', 'like', "%{$item['nombre_like']}%")->first();
            }

            if (! $producto) {
                continue;
            }

            ConfigRecomendacionProducto::updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'recomendacion' => $item['rec'],
                    'estado' => true,
                ]
            );
        }
    }
}
