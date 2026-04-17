<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Detallado de Ingresos y Gastos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
        }
        .info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #4CAF50;
            color: white;
        }
        .section-title {
            background-color: #f2f2f2;
            font-weight: bold;
            padding: 10px;
            margin-top: 20px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .balance-positive {
            color: green;
        }
        .balance-negative {
            color: red;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte Detallado de Ingresos y Gastos</h2>
        <p>Período: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
    </div>

    <!-- INGRESOS -->
    <div class="section-title">INGRESOS</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Profesional</th>
                <th>Método de Pago</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingresos as $ingreso)
            <tr>
                <td>{{ $ingreso['fecha'] }}</td>
                <td>{{ $ingreso['cliente'] }}</td>
                <td>{{ $ingreso['profesional'] }}</td>
                <td>{{ $ingreso['metodo_pago'] }}</td>
                <td class="text-right">${{ number_format($ingreso['monto'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL INGRESOS:</td>
                <td class="text-right">${{ number_format($totales['ingresos'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- GASTOS -->
    <div class="section-title">GASTOS</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gastos as $gasto)
            <tr>
                <td>{{ $gasto['fecha'] }}</td>
                <td>{{ $gasto['descripcion'] }}</td>
                <td class="text-right">${{ number_format($gasto['monto'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL GASTOS:</td>
                <td class="text-right">${{ number_format($totales['gastos'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BALANCE FINAL -->
    <table>
        <tr class="total-row">
            <td class="text-right" style="font-size: 14px;">BALANCE FINAL:</td>
            <td class="text-right {{ $totales['balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}" style="font-size: 14px;">
                ${{ number_format($totales['balance'], 2) }}
            </td>
        </tr>
    </table>
</body>
</html>
