<h1>Notificacion de asignacion de lectura</h1>
<p>Hola {{ $paciente->nombres }},</p>
<br>
<p>Es un gusto para nosotros informarte que hemos asignado la lectura de estudio {{ $estudio->study_desc }} realiado en
    la fecha {{ $estudio->fec_estudio }}</p>
<p>En un tiempo aproximado de {{ $estudio->tiempo }} te notificaremos por este medio del resultado de la lectura</p>
<br>
<p>Gracias por confiar en nosotros.</p>
