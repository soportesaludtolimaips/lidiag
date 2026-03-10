# JSON NECESARIOS PARA LA INTERFACE

Se requieres los siguientes JSON para un flujo adecuado de la asingnacion de estudios radiologicos.


## Atenciones: Listado de atencionesnes por numero de documento

{
    atencion_id: 1234,
    fecha_atencion: '2026-02-26'
}

## Ordenes: Listado de ordenes por numero de atención

{
  cod_cups: '000001',
  nom_produc: 'RADIGRAFIA',
  cant: 2, //Cantidad de producto
  val_uni: 2000 //Valor unitario del peoducto
}

## Diagnosticos: Litado de diagnosticos por atención

{
  cod_diagnos: '000001',
  nom_diagnos: 'DOLOR'
}


# JSON NECESARIOS PARA EN ENVIO DE LECTURAS

{
  atencion_id: 1234,
  cod_cups: '000001',
  medico_id: 123,
  lectura: 'Lectura de estudio radiologico'
}
