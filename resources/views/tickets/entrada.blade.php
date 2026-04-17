<div style="font-family: monospace; font-size: 13px; width: 260px;">
    @if(empty($factura) || !$factura->id)
        <div style="color: red; text-align: center; font-weight: bold;">ERROR: No se encontraron datos de la factura.<br>Verifique el ID enviado.</div>
    @else
        <div style="text-align: center; font-weight: bold; font-size: 16px;">
            {{ $parqueadero->nombre ?? 'PARQUEADERO' }}
        </div>
        <div style="text-align: center;">
            {{ $parqueadero->direccion ?? '' }}<br>
            NIT: {{ $parqueadero->nit ?? '' }}
        </div>
        <hr>
        <div>Fecha/Hora entrada: <b>{{ $factura->fecha_entrada }}</b></div>
        <div>Factura: <b>{{ $factura->id }}</b></div>
        <div>Placa: <b>{{ $factura->placa }}</b></div>
        <div>Tipo vehículo: <b>{{ $factura->tipoVehiculo->nombre ?? '' }}</b></div>
        <div style="margin: 10px 0; text-align: center;">
            {!! $barcode !!}
            <div style="font-size: 12px;">{{ $factura->placa }}</div>
        </div>
        <hr>
        <div style="text-align: center; font-size: 12px;">¡Gracias por su visita!</div>
    @endif
</div>
