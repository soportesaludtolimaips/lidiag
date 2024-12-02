
/**
1. Se realiza la consulta
**/
select * from admAtencion where IdAtencion = 6203297
select * from admCliente where IdCliente = 293002
select * from hceConsulta where IdAtencion = 6203297

/**
2. Orde de procedimeintos procedimientos
**/
select * from hceProceOrden where IdAtencion = 6203297
select * from hceProceDetalle where IdOrdenPro = 5317770

/**
3. Orden de medicamentos
**/
select * from hceFormuOrden where IdAtencion = 6203297
select * from hceFormulacionDet where IdFormula = 3169679

select top(10) * from hceConsulta 