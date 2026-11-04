<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificación Digital de Informe — LIDiag</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
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
            max-width: 640px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06), 0 8px 10px -6px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .cert-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            padding: 28px 24px;
            text-align: center;
            position: relative;
        }
        .cert-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .cert-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .cert-sub {
            font-size: 13px;
            opacity: 0.9;
        }
        .cert-body {
            padding: 24px;
        }
        .status-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .status-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #10b981;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .status-text h4 {
            font-size: 15px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 2px;
        }
        .status-text p {
            font-size: 12px;
            color: #047857;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (min-width: 500px) {
            .info-grid { grid-template-columns: 1fr 1fr; }
        }
        .info-item {
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }
        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .crypto-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            font-family: monospace;
            font-size: 11px;
            color: #475569;
            word-break: break-all;
            margin-bottom: 20px;
        }
        .crypto-title {
            font-weight: bold;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #334155;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cert-footer {
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div class="cert-card">
        <div class="cert-header">
            <div class="cert-badge">
                <i class="fa fa-shield"></i> VALIDACIÓN DIGITAL OFICIAL
            </div>
            <h1 class="cert-title">Constancia de Autenticidad</h1>
            <p class="cert-sub">Plataforma de Teleradiología e Imágenes Diagnósticas LIDiag</p>
        </div>

        <div class="cert-body">
            <div class="status-box">
                <div class="status-icon">
                    <i class="fa fa-check"></i>
                </div>
                <div class="status-text">
                    <h4>Documento Auténtico y Firmado Digitalmente</h4>
                    <p>La integridad del informe ha sido certificada criptográficamente conforme a la Ley 527 de 1999.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Sede Hospitalaria</div>
                    <div class="info-value">{{ $sedeNombre }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Paciente (Protegido)</div>
                    <div class="info-value">{{ $pacienteNombre }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Documento Identidad</div>
                    <div class="info-value">{{ $documentoPaciente }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Código CUPS</div>
                    <div class="info-value">{{ $codCups }}</div>
                </div>
                <div class="info-item full-width">
                    <div class="info-label">Estudio Clínico Realizado</div>
                    <div class="info-value">{{ $estudioNombre }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Médico Radiólogo Firmante</div>
                    <div class="info-value">{{ $medicoNombre }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Registro Médico</div>
                    <div class="info-value">{{ $registroMedico }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Toma</div>
                    <div class="info-value">{{ $fechaEstudio }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Firma Digital</div>
                    <div class="info-value">{{ $fechaLectura }}</div>
                </div>
            </div>

            <div class="crypto-box">
                <div class="crypto-title">
                    <i class="fa fa-lock text-primary"></i> Huella Digital Criptográfica (SHA-256)
                </div>
                {{ $hashIntegridad }}
            </div>
        </div>

        <div class="cert-footer">
            Verificado el {{ $timestampVerificacion }} • Sistema de Gestión de Imágenes LIDiag • Resolución 1995 de 1999
        </div>
    </div>
</body>
</html>
