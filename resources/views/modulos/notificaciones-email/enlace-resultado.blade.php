<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Su resultado está disponible</title>
</head>
<body style="margin:0;padding:0;background:#eef3f8;font-family:Arial,Helvetica,sans-serif;color:#1f2d3d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f8;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;padding:28px 24px;">
                <tr>
                    <td>
                        <p style="margin:0 0 16px;font-size:18px;font-weight:bold;color:#0b5ed7;">LIDiag · Resultados</p>
                        <p style="margin:0 0 12px;font-size:16px;line-height:24px;">
                            {{ $nombre ? 'Hola '.$nombre.',' : 'Hola,' }}
                        </p>
                        <p style="margin:0 0 20px;font-size:16px;line-height:24px;">
                            Su resultado de imágenes diagnósticas{{ $sede ? ' en '.$sede : '' }} ya está disponible.
                            Puede consultar el informe y las imágenes de su estudio desde su teléfono o computador.
                        </p>
                        <p style="margin:0 0 24px;text-align:center;">
                            <a href="{{ $url }}" style="display:inline-block;background:#0b5ed7;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 28px;border-radius:10px;">Ver mi resultado</a>
                        </p>
                        <p style="margin:0 0 8px;font-size:13px;line-height:20px;color:#5b6b7c;">
                            Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
                            <span style="word-break:break-all;">{{ $url }}</span>
                        </p>
                        <p style="margin:16px 0 0;font-size:13px;line-height:20px;color:#5b6b7c;">
                            Este enlace es personal y vence en {{ $diasVigencia }} {{ $diasVigencia === 1 ? 'día' : 'días' }}. No lo comparta: da acceso a sus resultados.
                            Si usted no se realizó este estudio, ignore este mensaje y no abra el enlace.
                        </p>
                        <p style="margin:16px 0 0;font-size:12px;color:#8a97a5;">Este mensaje se envió automáticamente; por favor no lo responda.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
