<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $cotizacion->codigo }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .header {
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #3B82F6;
        }
        .logo span {
            color: #1F2937;
        }
        .quote-info {
            text-align: right;
        }
        .quote-info h2 {
            margin: 0;
            color: #1F2937;
            font-size: 20px;
            text-transform: uppercase;
        }
        .quote-info p {
            margin: 4px 0 0;
            color: #6B7280;
        }
        .client-info {
            margin-bottom: 30px;
        }
        .client-info h3 {
            margin-top: 0;
            color: #1F2937;
            font-size: 16px;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 5px;
        }
        .client-info p {
            margin: 4px 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background-color: #F3F4F6;
            color: #374151;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
        }
        table.items td {
            border-bottom: 1px solid #E5E7EB;
            padding: 12px 10px;
            vertical-align: top;
        }
        .item-name {
            font-weight: bold;
            color: #1F2937;
            display: block;
            margin-bottom: 4px;
        }
        .item-specs {
            font-size: 12px;
            color: #6B7280;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            width: 300px;
            float: right;
        }
        .total-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-section th, .total-section td {
            padding: 10px;
        }
        .total-row {
            background-color: #F3F4F6;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo">PC<span>MATCH</span></div>
                </td>
                <td class="quote-info">
                    <h2>Cotización</h2>
                    <p>Código: <strong>{{ $cotizacion->codigo }}</strong></p>
                    <p>Fecha: {{ date('d/m/Y', strtotime($cotizacion->created_at)) }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="client-info">
        <h3>Preparado para:</h3>
        <p><strong>{{ $user->nombre }} {{ $user->apellido ?? '' }}</strong></p>
        <p>Email: {{ $user->correo }}</p>
        <p>Perfil de uso sugerido: <strong>{{ ucfirst($cotizacion->perfil) }}</strong></p>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%;">Componente</th>
                <th class="text-center" style="width: 15%;">Cantidad</th>
                <th class="text-right" style="width: 15%;">Precio Unit.</th>
                <th class="text-right" style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <span class="item-name">{{ $item->nombre_producto }}</span>
                    <span class="item-specs">{{ $item->categoria }} | {{ Str::limit($item->especificacion, 100) }}</span>
                    @if($item->nombre_bodega || $item->nombre_proveedor)
                    <br><span class="item-specs" style="color: #9CA3AF; font-size: 11px;">
                        Distribuye: {{ $item->nombre_bodega ?? 'N/A' }} 
                        @if($item->nombre_proveedor) ({{ $item->nombre_proveedor }}) @endif
                    </span>
                    @endif
                </td>
                <td class="text-center">{{ $item->cantidad }}</td>
                <td class="text-right">${{ number_format($item->precio_unitario, 2) }}</td>
                <td class="text-right">${{ number_format($item->precio_unitario * $item->cantidad, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table>
            <tr class="total-row">
                <td class="text-right">Total:</td>
                <td class="text-right">${{ number_format($cotizacion->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento es una cotización generada automáticamente. Los precios y disponibilidad están sujetos a cambios por parte de los proveedores.
        <br><br>
        <strong>PCMATCH</strong> — La plataforma para construir tu PC ideal
    </div>
</body>
</html>
