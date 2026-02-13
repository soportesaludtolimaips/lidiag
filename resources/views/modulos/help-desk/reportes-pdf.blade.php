<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Helpdesk - Reportes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1,h2,h3 { margin: 0 0 8px 0; }
        .muted { color: #666; }
        .row { width: 100%; margin-bottom: 10px; }
        .kpi { display: inline-block; width: 24%; padding: 8px; border: 1px solid #ddd; margin-right: 1%; box-sizing: border-box; }
        .kpi strong { font-size: 14px; display: block; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    @php
        $filters = $data['filters'] ?? [];
    @endphp

    <h1>Helpdesk - Reportes</h1>
    <div class="muted">
        Rango: <strong>{{ $filters['fechaIni'] ?? '' }}</strong> a <strong>{{ $filters['fechaFin'] ?? '' }}</strong>
        @if(!empty($filters['sedeId']))
            | Sede ID: <strong>{{ $filters['sedeId'] }}</strong>
        @endif
    </div>

    <div class="row" style="margin-top: 12px;">
        <div class="kpi">
            <strong>Total</strong>
            {{ $data['total'] ?? 0 }}
        </div>
        <div class="kpi">
            <strong>Abiertos</strong>
            {{ data_get($data, 'abiertos_vs_resueltos.abiertos', 0) }}
        </div>
        <div class="kpi">
            <strong>Resueltos</strong>
            {{ data_get($data, 'abiertos_vs_resueltos.resueltos', 0) }}
        </div>
        <div class="kpi">
            <strong>Prom. resolución (hrs)</strong>
            {{ $data['promedio_resolucion_horas'] ?? 'N/A' }}
        </div>
    </div>

    <h3>Totales por estado</h3>
    <table class="small">
        <thead>
        <tr>
            <th>Estado</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($data['totales_por_estado'] ?? []) as $r)
            <tr>
                <td>{{ $r['estado'] ?? '' }}</td>
                <td>{{ $r['total'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 14px;">Tickets por mes</h3>
    <table class="small">
        <thead>
        <tr>
            <th>Mes</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($data['tickets_por_mes'] ?? []) as $r)
            <tr>
                <td>{{ $r['mes'] ?? '' }}</td>
                <td>{{ $r['total'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 14px;">Tickets por categoría</h3>
    <table class="small">
        <thead>
        <tr>
            <th>Categoría</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($data['tickets_por_categoria'] ?? []) as $r)
            <tr>
                <td>{{ $r['categoria'] ?? '' }}</td>
                <td>{{ $r['total'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 14px;">Top solicitantes</h3>
    <table class="small">
        <thead>
        <tr>
            <th>Solicitante</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($data['tickets_por_solicitante'] ?? []) as $r)
            <tr>
                <td>{{ $r['solicitante'] ?? '' }}</td>
                <td>{{ $r['total'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 14px;">Top técnicos</h3>
    <table class="small">
        <thead>
        <tr>
            <th>Técnico</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($data['tickets_por_tecnico'] ?? []) as $r)
            <tr>
                <td>{{ $r['tecnico'] ?? '' }}</td>
                <td>{{ $r['total'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

