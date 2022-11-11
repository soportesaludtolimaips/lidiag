<?php

namespace Database\Seeders\configuracion;

use App\Models\Configuracion\ConfigAdminSalud;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class ConfigAdminSaludSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        ConfigAdminSalud::create(['cod_admin_salud' => 'EAS016', 'nit_admin_salud' => '890904996-1', 'nom_admin_salud' => 'Empresas Públicas de Medellín Departamento Médico']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EAS027', 'nit_admin_salud' => '800112806-2', 'nom_admin_salud' => 'Fondo de Pasivo Social de Ferrocarriles']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS001', 'nit_admin_salud' => '830113831-0', 'nom_admin_salud' => 'Aliansalud EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS002', 'nit_admin_salud' => '800130907-4', 'nom_admin_salud' => 'Salud Total S.A.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS003', 'nit_admin_salud' => '800140949-6', 'nom_admin_salud' => 'Cafesalud EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS005', 'nit_admin_salud' => '800251440-6', 'nom_admin_salud' => 'E.P.S Sanitas']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS008', 'nit_admin_salud' => '860066942-7', 'nom_admin_salud' => 'Compensar Entidad Promotora de Salud']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS010', 'nit_admin_salud' => '800088702-2', 'nom_admin_salud' => 'EPS Sura']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS012', 'nit_admin_salud' => '890303093-5', 'nom_admin_salud' => 'Comfenalco Valle EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS016', 'nit_admin_salud' => '805000427-1', 'nom_admin_salud' => 'Coomeva EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS017', 'nit_admin_salud' => '830003564-7', 'nom_admin_salud' => 'Famisanar']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS018', 'nit_admin_salud' => '805001157-2', 'nom_admin_salud' => 'Servicio Occidental de Salud S.O.S. S.A.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS023', 'nit_admin_salud' => '830009783-0', 'nom_admin_salud' => 'Cruz Blanca S.A']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS033', 'nit_admin_salud' => '830074184-5', 'nom_admin_salud' => 'Saludvida S.A EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS037', 'nit_admin_salud' => '900156264-2', 'nom_admin_salud' => 'Nueva EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'MIN001', 'nit_admin_salud' => '900462447-5', 'nom_admin_salud' => 'Fondo de Solidaridad y Garantía Fosyga']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'MIN002', 'nit_admin_salud' => '900462447-5', 'nom_admin_salud' => 'Fondo de Solidaridad y Garantía Fosyga']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'MIN003', 'nit_admin_salud' => '900462447-5', 'nom_admin_salud' => 'Fondo de Solidaridad y Garantía Fosyga']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES005', 'nit_admin_salud' => '890102257-3', 'nom_admin_salud' => 'Universidad del Atlántico']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES006', 'nit_admin_salud' => '890203183-0', 'nom_admin_salud' => 'Universidad Industrial de Santander']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES007', 'nit_admin_salud' => '890399010-6', 'nom_admin_salud' => 'Universidad del Valle']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES008', 'nit_admin_salud' => '899999063-3', 'nom_admin_salud' => 'Universidad Nacional de Colombia']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES009', 'nit_admin_salud' => '891500319-2', 'nom_admin_salud' => 'Universidad del Cauca']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES011', 'nit_admin_salud' => '890980040-8', 'nom_admin_salud' => 'Universidad de Antioquia']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES012', 'nit_admin_salud' => '891080031-3', 'nom_admin_salud' => 'Universidad de Córdoba']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'RES014', 'nit_admin_salud' => '891800330-1', 'nom_admin_salud' => 'Universidad Pedagógica y Tecnológica de Colombia - UPTC']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSC03', 'nit_admin_salud' => '8001409496', 'nom_admin_salud' => 'Cafesalud Entidad  Promotora de Salud S.A']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSC22', 'nit_admin_salud' => '899999107', 'nom_admin_salud' => 'Entidad Administradora de Régimen Subsidiado Convida']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSC25', 'nit_admin_salud' => '891856000', 'nom_admin_salud' => 'Capresoca EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSC34', 'nit_admin_salud' => '900298372', 'nom_admin_salud' => 'Capital Salud EPSS S.A.S.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC1', 'nit_admin_salud' => '824001398', 'nom_admin_salud' => 'Asociación de Cabildos Indígenas del Cesar “Dusakawi”']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC2', 'nit_admin_salud' => '812002376', 'nom_admin_salud' => 'Asociación de Cabildos Indígenas del Resguardo Indígena Zenú de San Andrés de Sotavento Córdoba - Sucre "Manexka"']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC3', 'nit_admin_salud' => '817001773', 'nom_admin_salud' => 'Asociación Indígena del Cauca - A.I.C.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC4', 'nit_admin_salud' => '839000495', 'nom_admin_salud' => 'Entidad Promotora de Salud Anas Wayuu EPSI']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC5', 'nit_admin_salud' => '837000084', 'nom_admin_salud' => 'Entidad Promotora de Salud Mallamas EPSI']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPSIC6', 'nit_admin_salud' => '809008362', 'nom_admin_salud' => 'Entidad Promotora de Salud Pijaosalud EPSI']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC02', 'nit_admin_salud' => '811004055', 'nom_admin_salud' => 'Empresa Mutual para el desarrollo integral de la salud E.S.S. Emdisalud ESS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC07', 'nit_admin_salud' => '806008394', 'nom_admin_salud' => 'Asociación Mutual Ser Empresa Solidaría de Salud ESS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC18', 'nit_admin_salud' => '814000337', 'nom_admin_salud' => 'Asociación Mutual Empresa Solidaria de Salud de Nariño E.S.S. Emssanar E.S.S.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC24', 'nit_admin_salud' => '800249241', 'nom_admin_salud' => 'Cooperativa de Salud y Desarrollo Integral de la Zona Sur Oriental de Cartagena  b aLtda.  Coosalud E.S.S.']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC33', 'nit_admin_salud' => '804002105', 'nom_admin_salud' => 'Cooperativa de Salud Comunitaria "Comparta"']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC62', 'nit_admin_salud' => '817000248', 'nom_admin_salud' => 'Asociación Mutual La Esperanza Asmet Salud']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC76', 'nit_admin_salud' => '818000140', 'nom_admin_salud' => 'Asociación Mutual Barrios Unidos de Quibdó E.S.S. AMBUQ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'ESSC91', 'nit_admin_salud' => '832000760', 'nom_admin_salud' => 'Entidad Cooperativa Solidaria de Salud Ecoopsos']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'EPS040', 'nit_admin_salud' => '900604350', 'nom_admin_salud' => 'Caja de Compensación Familiar de Antioquía - Comfama - Hoy Savia Salud EPS']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC09', 'nit_admin_salud' => '891800213', 'nom_admin_salud' => 'Comfaboy EPS-CCF de Boyacá']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC15', 'nit_admin_salud' => '891080005', 'nom_admin_salud' => 'Comfacor EPS – CCF de Córdoba']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC20', 'nit_admin_salud' => '891600091', 'nom_admin_salud' => 'Comfachoco – CCF del Chocó']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC23', 'nit_admin_salud' => '892115006', 'nom_admin_salud' => 'Caja de Compensación Familiar de La Guajira']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC24', 'nit_admin_salud' => '891180008', 'nom_admin_salud' => 'Comfamiliar Huila EPS – CCF']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC27', 'nit_admin_salud' => '891280008', 'nom_admin_salud' => 'Comfamiliar de Nariño EPS – CCF']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC33', 'nit_admin_salud' => '892200015', 'nom_admin_salud' => 'Comfasucre EPS-CCF de Sucre']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC53', 'nit_admin_salud' => '860045904', 'nom_admin_salud' => 'Comfacundi - CCF de Cundinamarca']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCFC55', 'nit_admin_salud' => '890102044', 'nom_admin_salud' => 'Cajacopi Atlántico  - CCF']);
        ConfigAdminSalud::create(['cod_admin_salud' => '230201', 'nit_admin_salud' => '800229739-0', 'nom_admin_salud' => 'Protección']);
        ConfigAdminSalud::create(['cod_admin_salud' => '230301', 'nit_admin_salud' => '800224808-8', 'nom_admin_salud' => 'Porvenir']);
        ConfigAdminSalud::create(['cod_admin_salud' => '230901', 'nit_admin_salud' => '800253055-2', 'nom_admin_salud' => 'Old Mutual Fondo de Pensiones Obligatorias']);
        ConfigAdminSalud::create(['cod_admin_salud' => '230904', 'nit_admin_salud' => '830125132-2', 'nom_admin_salud' => 'Old Mutual Fondo Alternativo de Pensiones']);
        ConfigAdminSalud::create(['cod_admin_salud' => '231001', 'nit_admin_salud' => '800227940-6', 'nom_admin_salud' => 'Colfondos']);
        ConfigAdminSalud::create(['cod_admin_salud' => '25-2', 'nit_admin_salud' => '860007379-8', 'nom_admin_salud' => 'Caja de Auxilios y de Prestaciones de ACDAC']);
        ConfigAdminSalud::create(['cod_admin_salud' => '25-3', 'nit_admin_salud' => '899999734-7', 'nom_admin_salud' => 'Fondo de Previsión Social del Congreso']);
        ConfigAdminSalud::create(['cod_admin_salud' => '25-7', 'nit_admin_salud' => '800216278-0', 'nom_admin_salud' => 'Pensiones de Antioquia']);
        ConfigAdminSalud::create(['cod_admin_salud' => '25-14', 'nit_admin_salud' => '900336004-7', 'nom_admin_salud' => 'Administradora Colombiana de Pensiones Colpensiones']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-4', 'nit_admin_salud' => '860002183-9', 'nom_admin_salud' => 'A.R.L. Seguros de Vida Colpatria S.A.']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-7', 'nit_admin_salud' => '860002503-2', 'nom_admin_salud' => 'Compañía de Seguros Bolívar S.A.']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-8', 'nit_admin_salud' => '860022137-5', 'nom_admin_salud' => 'Seguros de Vida Aurora']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-17', 'nit_admin_salud' => '860503617-3', 'nom_admin_salud' => 'ARP Alfa']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-18', 'nit_admin_salud' => '860008645-7', 'nom_admin_salud' => 'Liberty Seguros de Vida S.A.']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-23', 'nit_admin_salud' => '860011153-6', 'nom_admin_salud' => 'Positiva Compañía de Seguros']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-25', 'nit_admin_salud' => '800226175-3', 'nom_admin_salud' => 'Colmena Riesgos Profesionales']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-28', 'nit_admin_salud' => '800256161-9', 'nom_admin_salud' => 'ARL Sura']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-29', 'nit_admin_salud' => '830008686-1', 'nom_admin_salud' => 'La Equidad Seguros de Vida']);
        ConfigAdminSalud::create(['cod_admin_salud' => '14-30', 'nit_admin_salud' => '830054904-6', 'nom_admin_salud' => 'Mapfre Colombia Vida Seguros S.A']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF02', 'nit_admin_salud' => '890900840-1', 'nom_admin_salud' => 'Camacol']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF03', 'nit_admin_salud' => '890900842-6', 'nom_admin_salud' => 'Comfenalco Antioquia CCF']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF04', 'nit_admin_salud' => '890900841-9', 'nom_admin_salud' => 'Caja de Compensación Familiar de Antioquia']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF05', 'nit_admin_salud' => '890102044-1', 'nom_admin_salud' => 'Caja de Compensación Familiar Cajacopi Atlántico']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF06', 'nit_admin_salud' => '890102002-2', 'nom_admin_salud' => 'Combarranquilla']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF07', 'nit_admin_salud' => '890101994-9', 'nom_admin_salud' => 'Comfamiliar Atlántico']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF08', 'nit_admin_salud' => '890480023-7', 'nom_admin_salud' => 'Comfenalco Cartagena']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF09', 'nit_admin_salud' => '890480110-1', 'nom_admin_salud' => 'Caja de Compensación Familiar de Cartagena']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF10', 'nit_admin_salud' => '891800213-8', 'nom_admin_salud' => 'Comfaboy']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF11', 'nit_admin_salud' => '890806490-5', 'nom_admin_salud' => 'CCF de Caldas']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF13', 'nit_admin_salud' => '891190047-2', 'nom_admin_salud' => 'Comfaca']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF14', 'nit_admin_salud' => '891500182-0', 'nom_admin_salud' => 'Comfacauca']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF15', 'nit_admin_salud' => '892399989-8', 'nom_admin_salud' => 'Caja de Compensación Familiar Cesar']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF16', 'nit_admin_salud' => '891080005-1', 'nom_admin_salud' => 'Comfacor']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF21', 'nit_admin_salud' => '860013570-3', 'nom_admin_salud' => 'Cafam']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF22', 'nit_admin_salud' => '860007336-1', 'nom_admin_salud' => 'Colsubsidio']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF24', 'nit_admin_salud' => '860066942-7', 'nom_admin_salud' => 'Caja de Compensación Familiar Compensar']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF26', 'nit_admin_salud' => '860045904-7', 'nom_admin_salud' => 'Comfacundi']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF29', 'nit_admin_salud' => '891600091-8', 'nom_admin_salud' => 'CCF del Chocó']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF30', 'nit_admin_salud' => '892115006-5', 'nom_admin_salud' => 'Caja de Compensación Familiar de La Guajira']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF32', 'nit_admin_salud' => '891180008-2', 'nom_admin_salud' => 'Comfamiliar del Huila']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF33', 'nit_admin_salud' => '891780093-3', 'nom_admin_salud' => 'CCF del Magdalena']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF34', 'nit_admin_salud' => '892000146-3', 'nom_admin_salud' => 'Cofrem Meta']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF35', 'nit_admin_salud' => '891280008-1', 'nom_admin_salud' => 'CCF de Nariño']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF36', 'nit_admin_salud' => '890500675-6', 'nom_admin_salud' => 'Caja de Compensación Familiar del Oriente']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF37', 'nit_admin_salud' => '890500516-3', 'nom_admin_salud' => 'Caja de Comp. Familiar Comfanorte ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF38', 'nit_admin_salud' => '890270275-5', 'nom_admin_salud' => 'Caja de Comp. Familiar de Barrancabermeja Cafaba ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF39', 'nit_admin_salud' => '890200106-1', 'nom_admin_salud' => 'Cajasan ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF40', 'nit_admin_salud' => '890201578-7', 'nom_admin_salud' => 'Comfenalco Santander']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF41', 'nit_admin_salud' => '892200015-5', 'nom_admin_salud' => 'Caja de Compensación Familiar del Sucre']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF43', 'nit_admin_salud' => '890000381-0', 'nom_admin_salud' => 'Comfenalco Quindío']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF44', 'nit_admin_salud' => '891480000-1', 'nom_admin_salud' => 'Comfamiliar Risaralda']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF46', 'nit_admin_salud' => '890704737-0', 'nom_admin_salud' => 'CCF del Sur del Tolima Cafasur ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF48', 'nit_admin_salud' => '800211025-1', 'nom_admin_salud' => 'Comfatolima ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF50', 'nit_admin_salud' => '890700148-4', 'nom_admin_salud' => 'Comfenalco –Tolima']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF56', 'nit_admin_salud' => '890303093-5', 'nom_admin_salud' => 'Comfenalco Valle']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF57', 'nit_admin_salud' => '890303208-5', 'nom_admin_salud' => 'Comfandi ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF63', 'nit_admin_salud' => '891200337-8', 'nom_admin_salud' => 'Caja de Compensación Familiar del Putumayo']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF64', 'nit_admin_salud' => '892400320-5', 'nom_admin_salud' => 'Cajasai ']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF65', 'nit_admin_salud' => '800003122-6', 'nom_admin_salud' => 'Caja de Comp Familiar del Amazonas Cafamaz']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF67', 'nit_admin_salud' => '800219488-4', 'nom_admin_salud' => 'Comfiar Caja de Compensacion Familiar de Arauca']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF68', 'nit_admin_salud' => '800231969-4', 'nom_admin_salud' => 'Comcaja']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'CCF69', 'nit_admin_salud' => '844003392-8', 'nom_admin_salud' => 'Comfacasanare']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'PAESAP', 'nit_admin_salud' => '899999054-7', 'nom_admin_salud' => 'Escuela Superior de Administración Publica']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'PAICBF', 'nit_admin_salud' => '899999239-2', 'nom_admin_salud' => 'Instituto Colombiano de Bienestar Familiar']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'PAMIED', 'nit_admin_salud' => '899999001-7', 'nom_admin_salud' => 'Ministerio de Educación Nacional']);
        ConfigAdminSalud::create(['cod_admin_salud' => 'PASENA', 'nit_admin_salud' => '899999034-1', 'nom_admin_salud' => 'Servicio Nacional de Aprendizaje Sena']);
    }
}
