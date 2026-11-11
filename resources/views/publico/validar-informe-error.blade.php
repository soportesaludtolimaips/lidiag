<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error de Validación — LIDiag</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .cert-card {
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            text-align: center;
            padding: 36px 24px;
        }
        .error-icon {
            width: 64px;
            height: 64px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        h2 { font-size: 20px; font-weight: 800; color: #991b1b; margin-bottom: 8px; }
        p { font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 24px; }
        .help-box {
            background: #f1f5f9;
            padding: 14px;
            border-radius: 8px;
            font-size: 13px;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="cert-card">
        <div class="error-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h2>Código No Verificado</h2>
        <p>{{ $mensaje ?? 'El código proporcionado no pudo ser validado o ha expirado.' }}</p>
        <div class="help-box">
            Por favor verifique que el enlace o código QR haya sido escaneado directamente desde un informe oficial emitido por <strong>LIDiag</strong>.
        </div>
    </div>
</body>
</html>
