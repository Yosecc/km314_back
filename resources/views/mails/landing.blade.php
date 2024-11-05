<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje de Contacto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
        }
        p {
            color: #555555;
        }
        .info {
            margin-bottom: 15px;
        }
        .info strong {
            color: #333333;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Nuevo Mensaje {{ $landing['title'] }}</h2>
        <p>Has recibido un nuevo mensaje a través de un formulario</p>

        <div class="info">
             
            @foreach ($data['data'] as $key => $item)
            @if ($key != 'landing_id')
                
            <strong>{{  $key }}:</strong> {{ $item}}<br>
            @endif
            @endforeach
            {{-- <strong>Nombre:</strong> {{ $data['name'] }}<br>
            <strong>Email:</strong> {{ $data['email'] }}<br>
            <strong>Teléfono:</strong> {{ $data['phone'] }}<br> --}}
        </div>

        {{-- <p><strong>Mensaje:</strong></p>
        <p>{{ $data['body'] }}</p> --}}

        <div class="footer">
            <p>Este es un correo generado automáticamente. Por favor, no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
