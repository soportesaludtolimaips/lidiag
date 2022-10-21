<?php

namespace Database\Seeders\configuracion;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Configuracion\ConfigProducto;

class ConfigProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ConfigProducto::create(['cod_cups' => '876802',  'nom_produc' => 'CA DE SENO  XEROMAMOGRAFIA O MAMOGRAFIA BILATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '997300',  'nom_produc' => 'SALUD BUCAL DETARTRAJE SUPRAGINGIVAL SOD CONTROL MECANICO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '21601-CCG',  'nom_produc' => 'PortAtiles sin fluoroscopia e intensificador de imAgenes practicado en habitacion  UCI RN o quirofanos al valor del estudio agregar',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '871121',  'nom_produc' => 'PortAtiles con fluoroscopia y o intensificador de imagen practicado en quirofanos  al valor del estudio agregar',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => 'R03BB01',  'nom_produc' => 'Ioversol 320 mg iodo  x  ml Sln Iny Optiray',  'requi_lectura' => '0']);
        ConfigProducto::create(['cod_cups' => '870001',  'nom_produc' => 'RADIOGRAFIA DE CRANEO SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870101',  'nom_produc' => 'RADIOGRAFIA DE CARA [PERFILOGRAMA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870102',  'nom_produc' => 'RADIOGRAFIA DE ORBITAS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870103',  'nom_produc' => 'RADIOGRAFIA DE AGUJEROS OPTICOS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870105',  'nom_produc' => 'RADIOGRAFIA DE ARCO CIGOMATICO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870107',  'nom_produc' => 'RADIOGRAFIA DE HUESOS NASALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870108',  'nom_produc' => 'RADIOGRAFIA DE SENOS PARANASALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870112',  'nom_produc' => 'RADIOGRAFIA DE MAXILAR SUPERIOR',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '870113',  'nom_produc' => 'RADIOGRAFIA DE MAXILAR INFERIOR',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870131',  'nom_produc' => 'RADIOGRAFIA DE ARTICULACION TEMPOROMAXILAR',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870601',  'nom_produc' => 'RADIOGRAFIA DE TEJIDOS BLANDOS DE CUELLO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '870602',  'nom_produc' => 'RADIOGRAFIA DE CAVUM FARINGEO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871010',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA CERVICAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871019',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA UNION CERVICO DORSAL',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '871020',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA TORACICA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871030',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA DORSOLUMBAR',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871040',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA LUMBOSACRA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871050',  'nom_produc' => 'RADIOGRAFIA DE SACRO COCCIX',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871060',  'nom_produc' => 'RADIOGRAFIA DE COLUMNA VERTEBRAL TOTAL TEST DE ESCOLIOSIS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871070',  'nom_produc' => 'RADIOGRAFIA DINAMICA DE COLUMNA VERTEBRAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871091',  'nom_produc' => 'RADIOGRAFIA DE ARTICULACIONES SACROILIACAS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871111',  'nom_produc' => 'RADIOGRAFIA DE REJA COSTAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871112',  'nom_produc' => 'RADIOGRAFIA DE ESTERNON',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871121',  'nom_produc' => 'RADIOGRAFIA DE TORAX P A  O A P  LATERAL DECUBITO LATERAL  OBLICUAS O LATERAL CON BARIO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871129',  'nom_produc' => 'RADIOGRAFIA DE ARTICULACIONES ESTERNOCLAVICULARES',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '871402',  'nom_produc' => 'TOMOGRAFIA DE TORAX EN DOS PROYECCIONES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '871404',  'nom_produc' => 'TOMOGRAFIA LINEAL DE COLUMNA CERVICAL TORACCICA O LUMBAR',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '872002',  'nom_produc' => 'RADIOGRAFIA DE ABDOMEN SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '872011',  'nom_produc' => 'RADIOGRAFIA DE ABDOMEN SIMPLE CON PROYECCIONES  ADICIONALES SERIE DE ABDOMEN AGUDO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873002',  'nom_produc' => 'RADIOGRAFIA DE HUESOS LARGOS SERIE COMPLETA   ESQUELETO AXIAL Y APENDIPULAR',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873111',  'nom_produc' => 'RADIOGRAFIA DE OMOPLATO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873112',  'nom_produc' => 'RADIOGRAFIA DE CLAVICULA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873121',  'nom_produc' => 'RADIOGRAFIA DE HUMERO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873122',  'nom_produc' => 'RADIOGRAFIA DE ANTEBRAZO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873202',  'nom_produc' => 'RADIOGRAFIA DE ARTICULACIONES ACROMIO CLAVICULARES COMPARATIVAS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873204',  'nom_produc' => 'RADIOGRAFIA DE HOMBRO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873205',  'nom_produc' => 'RADIOGRAFIA DE CODO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873206',  'nom_produc' => 'RADIOGRAFIA DE MUÑECA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873210',  'nom_produc' => 'RADIOGRAFIA DE MANO Y O DEDOS DE MANO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873302',  'nom_produc' => 'RADIOGRAFIA PARA MEDICION DE  MIEMBROS INFERIORES [ESTUDIO DE FARILL U OSTEOMETRIA ESTUDIO DE PIE PLANO  PIES CON APOYO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873311',  'nom_produc' => 'RADIOGRAFIA DE ANTEVERSION FEMORAL',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873312',  'nom_produc' => 'RADIOGRAFIA DE FEMUR AP Y  LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873313',  'nom_produc' => 'RADIOGRAFIA DE PIERNA AP Y LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873314',  'nom_produc' => 'RADIOGRAFIA DE ANTEVERSION TIBIAL',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873333',  'nom_produc' => 'RADIOGRAFIA DE PIE AP Y LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873335',  'nom_produc' => 'RADIOGRAFIA DE CALCANEO AXIAL Y LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873340',  'nom_produc' => 'RADIOGRAFIA DE MIEMBRO INFERIOR  AP Y LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873411',  'nom_produc' => 'RADIOGRAFIA DE PELVIS O CADERA O ARTICULACION COXO FEMORAL AP LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873412',  'nom_produc' => 'RADIOGRAFIA DE PELVIS CADERA COMPARATIVA',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873420',  'nom_produc' => 'RADIOGRAFIA DE RODILLA AP LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873422',  'nom_produc' => 'RADIOGRAFIA DE RODILLAS COMPARATIVAS POSICION VERTICAL UNICAMENTE VISTA ANTEROPOSTERIOR',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873424',  'nom_produc' => 'RADIOGRAFIA TANGENCIAL DE ROTULA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873431',  'nom_produc' => 'RADIOGRAFIA DE TOBILLO AP LATERAL Y ROTACION INTERNA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873432',  'nom_produc' => 'RADIOGRAFIA DE ANTEPIE AP Y OBLICUA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873443',  'nom_produc' => 'RADIOGRAFIAS COMPARATIVAS DE EXTREMIDADES INFERIORES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873710',  'nom_produc' => 'TOMOGRAFIA OSTEOARTICULAR EN MIEMBRO SUPERIOR',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873720',  'nom_produc' => 'TOMOGRAFIA OSTEOARTICULAR EN MIEMBRO INFERIOR',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '876801',  'nom_produc' => 'XEROMAMOGRAFIA O MAMOGRAFIA UNILATERAL O DE PIEZA QUIRURGICA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '876802',  'nom_produc' => 'XEROMAMOGRAFIA O MAMOGRAFIA  BILATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879111',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879112',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO CON CONTRASTE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879113',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE Y CON CONTRASTE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879116',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE SILLA TURCA HIPOFISIS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879121',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE ORBITAS CORTES AXIALES Y CORONALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879122',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE OIDO PEÑASCO Y CONDUCTO AUDITIVO INTERNO CORTES AXIALES Y CORONALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879131',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE SENOS PARANASALES O CARA CORTES AXIALES Y CORONALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879150',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE ARTICULACION TEMPORO MANDIBULAR BILATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879161',  'nom_produc' => 'TOMOGRAFIA COMPUTADA DE CUELLO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879162',  'nom_produc' => 'TOMOGRAFIA  COMPUTADA DE LARINGE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879201',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE COLUMNA SEGMENTOS CERVICAL TORACICO  LUMBAR Y O SACRO  POR CADA NIVEL TRES ESPACIOS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879205',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE COLUMNA SEGMENTOS CERVICAL TORACICO  LUMBAR Y O SACRO  COMPLEMENTO A MIELOGRAFIA CADA SEGMENTO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879301',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE TORAX SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879391',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE TORAX EXTENDIDO AL ABDOMEN SUPERIOR CON SUPRARRENALES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879410',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE ABDOMEN SUPERIOR',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879420',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE ABDOMEN Y  PELVIS ABDOMEN TOTAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879460',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE PELVIS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879510',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE MIEMBROS SUPERIORES Y ARTICULACIONES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879520',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE MIEMBROS INFERIORES Y ARTICULACIONES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879523',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE MIEMBROS INFERIORES AXIALES DE  ROTULA O LONGITUD DE MIEMBROS INFERIORES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879910',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA EN RECONSTRUCCION TRIDIMENSIONAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879920',  'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA CON MODALIDAD DINAMICA SECUENCIA RAPIDA',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '881211',  'nom_produc' => 'ECOGRAFIA DIAGNOSTICA DE TORAX PERICARDIO O PLEURA',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '881302',  'nom_produc' => 'ECOGRAFIA DE ABDOMEN TOTAL HIGADO  PANCREAS VESICULA VIAS BILIARES RIÑONES BAZO GRANDES VASOS PELVIS Y FLANCOS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883101',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE CEREBRO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883102',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE BASE DE CRANEO SILLA TURCA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883210',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA CERVICAL SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883211',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA CERVICAL',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '883220',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA TORACICA SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883221',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA TORACICA CON CONTRASTE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883230',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA LUMBOSACRA SIMPLE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883231',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE COLUMNA LUMBAR CON CONTRASTE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883301',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE TORAX PROYECCIONES PA Y LATERAL',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883401',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE ABDOMEN',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883434',  'nom_produc' => 'COLANGIOGRAFIA X RESONANCIA NUCLEAR MAGNETICA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883440',  'nom_produc' => 'RESONANCIA MAGNETICA DE PELVIS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883511',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE MIEMBRO SUPERIOR SIN INCLUIR ARTICULACIONES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883512',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE ARTICULACIONES DE MIEMBRO SUPERIOR CODO HOMBRO Y O PUÑO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883521',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE MIEMBRO INFERIOR SIN INCLUIR ARTICULACIONES',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883522',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE ARTICULACIONES DE MIEMBRO INFERIOR PELVIS   RODILLA  PIE Y O CUELLO DE PIE',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883590',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA DE SISTEMA MUSCULO ESQUELETICO NCOC',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883901',  'nom_produc' => 'RESONANCIA NUCLEAR MAGNETICA CON GADOLINIO DTPA (COD SOAT 37301 Examen para magnetico (Gadolinio DTPA)  al valor del examen  agregar',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '890303BT',  'nom_produc' => 'SALUD BUCAL  TRATAMIENTO TERMINADOS',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => 'V08AB04',  'nom_produc' => 'Iopamidol 300 mg de Yodo ml Solucion Inyectable',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => 'V08AA99',  'nom_produc' => 'Diatrizoato de Meglumina 66 xciento mas  Diatrizoato de sodio 10 porciento Gastroview Dosis Oral',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '879201',  'nom_produc' => 'Tomografia Axial Computarizada de Columna Segmentos Cervical Toracico Lumbar Cada Espacio Adicional',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873004',  'nom_produc' => 'CARPOGRAMA  RADIOGRAFIA PARA DETECTAR EDAD OSEA',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '883111',  'nom_produc' => 'RESONANCIA MAGNETICA DE CABEZA  CARA Y CUELLO',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '879430',  'nom_produc' => 'TOMOGRAFIA COMPUTADA DE VIAS URINARIAS [UROTC]',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873123',  'nom_produc' => 'RADIOGRAFIAS COMPARATIVAS DE EXTREMIDADES SUPERIORES',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '871410',  'nom_produc' => 'TOMOGRAFIA DE TORAX AP',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '21601-CCG',  'nom_produc' => 'Portatiles sin fluoroscopia e intensificador de imAgenes practicado en habitacion  UCI RN o quirofanos al valor del estudio agregar (CRITICAL CARE GROUP)',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873204',  'nom_produc' => 'ST    RADIOGRAFIA DE HOMBRO',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '8732050',  'nom_produc' => 'ST RADIOGRAFIA DE CODO',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873312',  'nom_produc' => 'ST    RADIOGRAFIA DE FEMUR (AP, LATERAL)',  'requi_lectura' => '0	']);
        ConfigProducto::create(['cod_cups' => '873333',  'nom_produc' => 'ST RADIOGRAFIA DE PIE (AP, LATERAL Y OBLICUA)',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => '873420',  'nom_produc' => 'ST RADIOGRAFIA DE RODILLA (AP, LATERAL)',  'requi_lectura' => '1']);
        ConfigProducto::create(['cod_cups' => 'V08AB11',  'nom_produc' => 'IOBITRIDOL SOLUCION INYECTABLE XENETIX 300',  'requi_lectura' => '0	']);
    }
}
