<?php

ob_start();
include "../../../config/config_mysql.php";
include "../../../config/conexion_mysql.php";
include "../../../config/conexion_BASE_SAHI.php";
session_start();

if ($link_BASE_SAHI) {
    // comprobamos que sea una petición ajax
    $TodoBien = true;
    $Procedimientos = "";
    $fechaActual = strftime("%Y%m%d %H:%M:%S", time());

    $SqlMedicoEcogra = "SELECT * FROM gene_personal WHERE id_personal = ".$_SESSION['SesionUsuaEcograIdMedico'];
    $ResultMedicoEcogra = mysql_query($SqlMedicoEcogra, $link_mysql) or exit("Error: ".mysql_errno()." - ".mysql_error()." - ".$SqlMedicoEcogra);
    $RegisMedicoEcogra = mysql_fetch_array($ResultMedicoEcogra);
    $IdMedicoSAHI = $RegisMedicoEcogra['id_sahi'];
    $MedicoSAHI = $RegisMedicoEcogra['pri_nom']." ".$RegisMedicoEcogra['seg_nom']." ".$RegisMedicoEcogra['pri_ape']." ".$RegisMedicoEcogra['seg_ape'];

    /* BUSCO EL ID DEL MEDICO EN SAHI */
    $SqlMedico = "SELECT ASI_USUA.IdUsuario, hcePersonal.IdPersonal 
				FROM ASI_USUA INNER JOIN hcePersonal ON ASI_USUA.IdUsuario = hcePersonal.IdUsuario 
				WHERE (ASI_USUA.IdUsuario = ".$IdMedicoSAHI.")";

    $ResultMedico = sqlsrv_query($link_BASE_SAHI, $SqlMedico, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET]);
    $RegisMedico = sqlsrv_fetch_array($ResultMedico, SQLSRV_FETCH_ASSOC);

    $NumMedico = sqlsrv_num_rows($ResultMedico);

    if ($NumMedico === false) {
        exit(print_r(sqlsrv_errors(), true));
    }

    if ($NumMedico == 0) {
        echo "El médico no se encuentra configurado, por favor verifique que el médico este creado como usuario y que el login sea el mismo de SAHI. ".$SqlMedico;
        exit(0);
    }

    /* BUSCO LA UBICACION ACTUAL DE LA ATENCION */
    $SqlUbicacion = "SELECT IdUbicacionActual  FROM admAtencion  WHERE idAtencion = ".$_REQUEST['id_atencion'];
    $ResltUbicacion = sqlsrv_query($link_BASE_SAHI, $SqlUbicacion, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: - ".$SqlUbicacion.' - '.print_r(sqlsrv_errors(), true));
    $RegisUbicacion = sqlsrv_fetch_array($ResltUbicacion, SQLSRV_FETCH_ASSOC);

    /* GENERO EL CONSECUTIVO DEL ENCABEZADO DEL ESQUEMA */
    $SqlInsertConseEsque = "UPDATE sisTabla SET Consecutivo = Consecutivo + 1 WHERE IdTabla > 0 AND NomTabla = 'hceEsquemaEncabeza'";
    sqlsrv_query($link_BASE_SAHI, $SqlInsertConseEsque, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: INSERSION CONSECUTIVO DEL ENCABEZADO DEL ESQUEMA, - ".$SqlInsertConseEsque.' - '.print_r(sqlsrv_errors(), true));

    $SqlConseEsque = "SELECT Consecutivo as Consec FROM Sistabla WHERE Nomtabla = 'HCEEsquemaEncabeza'";
    $ReslutConseEsque = sqlsrv_query($link_BASE_SAHI, $SqlConseEsque, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: CONSULTA CONSECUTIVO DEL ENCABEZADO DEL ESQUEMA, - ".$SqlConseEsque.' - '.print_r(sqlsrv_errors(), true));
    $RegisConseEsque = sqlsrv_fetch_array($ReslutConseEsque, SQLSRV_FETCH_ASSOC);

    /* GENERO EL CONSECUTIVO DEL ENCABEZADO PADRE */
    $SqlInsertConseEncabePadre = "UPDATE HceEsquema Set IdConsecutivoEsq = IdConsecutivoEsq + 1 WHERE IdEsquema = 149";
    sqlsrv_query($link_BASE_SAHI, $SqlInsertConseEncabePadre, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: CONSECUTIVO DEL ENCABEZADO PADRE, - ".$SqlInsertConseEncabePadre.' - '.print_r(sqlsrv_errors(), true));

    $SqlConseEncabePadre = "SELECT Consecutivo as Consec FROM Sistabla WHERE Nomtabla = 'HCEEsquemaEncabeza'";
    $ReslutConseEncabePadre = sqlsrv_query($link_BASE_SAHI, $SqlConseEncabePadre, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: INSERT CONSECUTIVO DEL ENCABEZADO PADRE- ".$SqlConseEncabePadre.' - '.print_r(sqlsrv_errors(), true));
    $RegisConseEncabePadre = sqlsrv_fetch_array($ReslutConseEncabePadre, SQLSRV_FETCH_ASSOC);

    /* INSERTO EL ENCABEZADO DEL ESQUEMA */
    $SqlCabeEsque = "INSERT INTO hceEsquemaEncabeza(IdEsquemaEncabezado, IdEsquema,  idEncabezadoPadre, ValEsquemaConsecutivo,
								valDetConsecutivo, IdUbicacion, idAtencion,  ValUltimoNroRegistro, IndActivado, IndHabilitado,  IdUsuarioR, FecRegistro, IdProductoR, NomProducto, CodProducto, IdEstudio) 
					VALUES (".$RegisConseEsque['Consec'].", 149, ".$RegisConseEncabePadre['Consec']." , 1, 0,
							".$RegisUbicacion['IdUbicacionActual'].",".$_REQUEST['id_atencion'].", 1, 1, 1, 
							".$RegisMedico['IdUsuario'].", '".$fechaActual."', 0, '', '','')";
    $ResultCabeEsque = sqlsrv_query($link_BASE_SAHI, $SqlCabeEsque, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: ENCABEZADO DEL ESQUEMA, - ".$SqlCabeEsque.' - '.print_r(sqlsrv_errors(), true));
    $RegisCabeEsque = sqlsrv_fetch_array($ResultCabeEsque, SQLSRV_FETCH_ASSOC);

    /* INSERTO EL EL ESQUEMA DE LA ATENCION */
    $SqlAtenEsque = "INSERT INTO hceEsquemasdeAte(IdEsquemadeAtencion, IdEsquema, IdAtencion, IdUbicacion,  idTraslado, IdMedico,
								FecEsquema, IndHabilitado, IndActivado, FecCerrado, IdUsuarioR) 
					VALUES (".$RegisConseEsque['Consec'].", 149, ".$_REQUEST['id_atencion'].",".$RegisUbicacion['IdUbicacionActual'].", 
							1, ".$RegisMedico['IdPersonal'].", '".str_replace("-", "", $fechaActual)."', 1,1, '".$fechaActual."',
							".$RegisMedico['IdUsuario'].")";
    $ResltAtenEsque = sqlsrv_query($link_BASE_SAHI, $SqlAtenEsque, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: ESQUEMA DE LA ATENCION, - ".$SqlAtenEsque.' - '.print_r(sqlsrv_errors(), true));
    $RegisAtenEsque = sqlsrv_fetch_array($ResltAtenEsque, SQLSRV_FETCH_ASSOC);

    /* INSERTO EL ESQUEMA MEMO */
    $SqlEncabeMemo = "INSERT INTO hceEsquemaDetMemo(IdMemo, IdEsquemaEncabezado, DesMemo)
					VALUES (1, ".$RegisConseEsque['Consec'].",'".utf8_decode(trim($_REQUEST['texto'])."

Opinion: "
.trim($_REQUEST['opinion']))."


					')";
    $ResultEncabeMemo = sqlsrv_query($link_BASE_SAHI, $SqlEncabeMemo, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: ESQUEMA MEMO, - ".$SqlEncabeMemo.' - '.print_r(sqlsrv_errors(), true));

    $RegisEncabeMemo = sqlsrv_fetch_array($ResultEncabeMemo, SQLSRV_FETCH_ASSOC);

    $UltimaFechas = $fechaActual;
    $FechaEncabezaDetaProducto = substr($UltimaFechas, 0, 4)."-".substr($UltimaFechas, 4, 2)."-".substr($UltimaFechas, 6, 2)." ".substr($UltimaFechas, 9, 8);

    /* INSERTO EL MEDICO */
    $SqlEncabeMedico = "INSERT INTO hceEsquemaDetProc(IdDetalleValor, IdEsquemaEncabezado, ValNroRegistro, ValVarChar1, ValVarChar2, ValVarChar3, 
										ValVarChar4, ValVarChar5, ValVarChar6, ValVarChar7, ValVarChar8, ValVarChar9, ValVarChar10, ValVarChar11,
										ValVarChar12, ValVarChar13, ValVarChar14, ValVarChar15, ValVarChar16, ValVarChar17, ValVarChar18, ValVarChar19,
										ValVarChar20, IdUsuarioR, FecRegistro)
					VALUES (1, ".$RegisConseEsque['Consec'].", 1, '".$FechaEncabezaDetaProducto."', '".$FechaEncabezaDetaProducto."', '', 'MEMO1', 
							'', '', '0', '1551', '".$MedicoSAHI."', '', '', '', '', '', '', '', '', '', '', '',
							".$RegisMedico['IdUsuario'].", '".$fechaActual."')";
    $ResultEncabeMedico = sqlsrv_query($link_BASE_SAHI, $SqlEncabeMedico, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: INSERTO EL MEDICO, - ".$SqlEncabeMedico.' - '.print_r(sqlsrv_errors(), true));
    $RegisEncabeMedico = sqlsrv_fetch_array($ResultEncabeMedico, SQLSRV_FETCH_ASSOC);

    /* GENERO EL CONSECUTIVO DEL SEGUNDO ENCABEZADO DEL ESQUEMA */
    $SqlInsertConseEsque2 = "UPDATE sisTabla SET Consecutivo = Consecutivo + 1 WHERE IdTabla > 0 AND NomTabla = 'hceEsquemaEncabeza'";
    sqlsrv_query($link_BASE_SAHI, $SqlInsertConseEsque2, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: GENRO CONSECUTIVO SEGUNDO ENCABEZADO DEL ESQUEMA, - ".$SqlInsertConseEsque2.' - '.print_r(sqlsrv_errors(), true));

    $SqlConseEsque2 = "SELECT Consecutivo as Consec FROM Sistabla WHERE Nomtabla = 'HCEEsquemaEncabeza'";
    $ResultConseEsque2 = sqlsrv_query($link_BASE_SAHI, $SqlConseEsque2, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: CONSULTO CONSECUTIVO SEGUNDO ENCABEZADO DEL ESQUEMA, - ".$SqlConseEsque2.' - '.print_r(sqlsrv_errors(), true));
    $RegisConseEsque2 = sqlsrv_fetch_array($ResultConseEsque2, SQLSRV_FETCH_ASSOC);

    /* INSERTO EL SEGUNDO ESQUEMA ENCABEZADO */
    $SqlCabeEsque2 = "INSERT INTO hceEsquemaEncabeza(IdEsquemaEncabezado, IdEsquema,  idEncabezadoPadre, ValEsquemaConsecutivo,
								valDetConsecutivo, IdUbicacion, idAtencion,  ValUltimoNroRegistro, IndActivado, IndHabilitado,  IdUsuarioR, FecRegistro, IdProductoR, NomProducto, CodProducto, IdEstudio) 
					VALUES (".$RegisConseEsque2['Consec'].", 150, ".$RegisConseEsque['Consec'].", 1, 0, ".$RegisUbicacion['IdUbicacionActual'].",
							".$_REQUEST['id_atencion'].", 1, 1, 1, ".$RegisMedico['IdUsuario'].", '".$fechaActual."', 0, '', '','')";
    $ResltCabeEsque2 = sqlsrv_query($link_BASE_SAHI, $SqlCabeEsque2, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
            or exit("Error MsSql: SEGUNDO ESQUEMA ENCABEZADO, - ".$SqlCabeEsque2.' - '.print_r(sqlsrv_errors(), true));

    $RegisCabeEsque2 = sqlsrv_fetch_array($ResltCabeEsque2, SQLSRV_FETCH_ASSOC);

    /* INSERTO LOS PROCEDIMIENTOS QUE SE LE REALIZARON AL PACIENTE */
    $sqlProcedimientos = "SELECT config_productos_sahi.`id_produc`, `config_productos_sahi`.`nom_produc`
						FROM `agenda_productos`
						    INNER JOIN `config_productos_sahi` ON (`agenda_productos`.`id_produc` = `config_productos_sahi`.`id_produc`)
						    INNER JOIN `agenda_agendar` ON (`agenda_productos`.`id_agenda` = `agenda_agendar`.`id_agenda`)
						WHERE (`agenda_agendar`.`id_agenda` = ".$_REQUEST['id_agenda'].")";

    $resultProcedimientos = mysql_query($sqlProcedimientos, $link_mysql);
    $i = 0;
    while ($regisProcedimientos = mysql_fetch_array($resultProcedimientos)) {
        /* LISTO EL PRODUCTO */
        $SqlProduc = "SELECT IdProducto, NomProducto, CodLegal
					FROM proProducto
					WHERE (IdProducto = ".$regisProcedimientos['id_produc'].")";
        $ResltProduc = sqlsrv_query($link_BASE_SAHI, $SqlProduc, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
        or exit("Error MsSql: LISTAR PRODUCTOS, - ".$SqlProduc.' - '.print_r(sqlsrv_errors(), true));
        $RegisProduc = sqlsrv_fetch_array($ResltProduc, SQLSRV_FETCH_ASSOC);

        /* INSERTO EL PRODUCTO EN LA HISTORIA CLINICA */
        $i++;
        $SqlProcedi = "INSERT INTO hceEsquemaDetProc(IdDetalleValor, IdEsquemaEncabezado, ValNroRegistro, ValVarChar1, ValVarChar2,
							ValVarChar3, ValVarChar4, ValVarChar5, ValVarChar6, ValVarChar7, ValVarChar8, ValVarChar9, ValVarChar10,
							ValVarChar11, ValVarChar12, ValVarChar13, ValVarChar14, ValVarChar15, ValVarChar16, ValVarChar17, ValVarChar18,
							ValVarChar19, ValVarChar20, IdUsuarioR, FecRegistro) 
					VALUES (".$i.", ".$RegisConseEsque2['Consec'].", 1, '".$RegisProduc['IdProducto']."', '".$RegisProduc['NomProducto']."',
							'".$RegisProduc['CodLegal']."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ".$RegisMedico['IdUsuario'].", '".$fechaActual."')";
        $ResltProcedi = sqlsrv_query($link_BASE_SAHI, $SqlProcedi, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET])
        or exit("Error MsSql: - ".$SqlProcedi.' - '.print_r(sqlsrv_errors(), true));
        $RegisProcedi = sqlsrv_fetch_array($ResltProcedi, SQLSRV_FETCH_ASSOC);
    }

    $sql = "UPDATE lecturas SET envio_sahi = 1 WHERE id_lectura = ".$_POST['id_lectura'];
    $resul = mysql_query($sql, $link_mysql) or exit("Error: ".mysql_errno()." - ".mysql_error()." - ".$sql);
    sqlsrv_close($link_BASE_SAHI);
    ob_end_flush();
    echo 1;
    exit(0);
} else {
    $sql = "UPDATE lecturas SET envio_sahi = 0 WHERE id_lectura = ".$_POST['id_lectura'];
    $resul = mysql_query($sql, $link_mysql) or exit("Error: ".mysql_errno()." - ".mysql_error()." - ".$sql);

    echo 2;
    exit();

}
