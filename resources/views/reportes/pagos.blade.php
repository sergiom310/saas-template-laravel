<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Pagos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        h1 {
            text-align: center;
            color: #455A64;
            font-size: 18px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #607D8B;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .info {
            margin-bottom: 15px;
            color: #666;
        }
        .total {
            margin-top: 15px;
            text-align: right;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Reporte de Pagos</h1>

    <div class="info">
        <strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        @if(isset($filtros))
            @if(!empty($filtros['cliente']))
                <strong>Cliente:</strong> {{ $filtros['cliente'] }}<br>
            @endif
            @if(!empty($filtros['profesional']))
                <strong>Profesional:</strong> {{ $filtros['profesional'] }}<br>
            @endif
            @if(!empty($filtros['fecha']))
                <strong>Fecha:</strong> {{ date('d/m/Y', strtotime($filtros['fecha'])) }}<br>
            @endif
            @if(!empty($filtros['metodo_pago']))
                <strong>Método de Pago:</strong> {{ $filtros['metodo_pago'] }}<br>
            @endif
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Profesional</th>
                <th>Procedimiento</th>
                <th>Fecha Cita</th>
                <th>Franja</th>
                <th>Monto</th>
                <th>Método</th>
                <th>Fecha Pago</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagos as $index => $pago)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $pago['cliente'] }}</td>
                <td>{{ $pago['profesional'] }}</td>
                <td>{{ $pago['procedimiento'] }}</td>
                <td>{{ $pago['fecha_cita'] ? date('d/m/Y', strtotime($pago['fecha_cita'])) : 'N/A' }}</td>
                <td>{{ $pago['franja_horaria'] }}</td>
                <td>${{ number_format($pago['monto'], 0, ',', '.') }}</td>
                <td>{{ $pago['metodo_pago_detalle'] }}</td>
                <td>{{ $pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : 'N/A' }}</td>
                <td>{{ ucfirst($pago['estado']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <strong>Total de registros:</strong> {{ count($pagos) }}<br>
        <strong>Total en pagos:</strong> ${{ number_format(array_sum(array_column($pagos, 'monto')), 0, ',', '.') }}
    </div>
</body>
</html>
