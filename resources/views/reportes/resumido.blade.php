<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Resumido de Ingresos y Gastos</title>
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
        <h2>Reporte Resumido de Ingresos y Gastos</h2>
        <p>Período: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
    </div>

    <!-- INGRESOS POR MÉTODO DE PAGO -->
    <div class="section-title">INGRESOS POR MÉTODO DE PAGO</div>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingresos_por_metodo as $metodo)
            <tr>
                <td>{{ $metodo['metodo'] }}</td>
                <td class="text-right">{{ $metodo['cantidad'] }}</td>
                <td class="text-right">${{ number_format($metodo['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- INGRESOS POR PROFESIONAL -->
    <div class="section-title">INGRESOS POR PROFESIONAL</div>
    <table>
        <thead>
            <tr>
                <th>Profesional</th>
                <th class="text-right">Cantidad de Citas</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingresos_por_profesional as $prof)
            <tr>
                <td>{{ $prof['profesional'] }}</td>
                <td class="text-right">{{ $prof['cantidad'] }}</td>
                <td class="text-right">${{ number_format($prof['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALES -->
    <div class="section-title">RESUMEN GENERAL</div>
    <table>
        <tr class="total-row">
            <td>TOTAL INGRESOS:</td>
            <td class="text-right">${{ number_format($totales['ingresos'], 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL GASTOS:</td>
            <td class="text-right">${{ number_format($totales['gastos'], 2) }}</td>
        </tr>
        <tr class="total-row" style="font-size: 14px;">
            <td>BALANCE FINAL:</td>
            <td class="text-right {{ $totales['balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                ${{ number_format($totales['balance'], 2) }}
            </td>
        </tr>
    </table>
</body>
</html>
