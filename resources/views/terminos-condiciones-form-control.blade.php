<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $terminosCondiciones->titulo ?? 'Términos y Condiciones' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2em;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        
        .contenido {
            font-size: 1rem;
        }
        
        .contenido h2 {
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .contenido h3 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 12px;
            font-size: 1.3em;
        }
        
        .contenido p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        .contenido ul, .contenido ol {
            margin: 15px 0 15px 30px;
        }
        
        .contenido li {
            margin-bottom: 8px;
        }
        
        .contenido strong {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .contenido a {
            color: #3498db;
            text-decoration: none;
        }
        
        .contenido a:hover {
            text-decoration: underline;
        }
        
        .contenido blockquote {
            border-left: 4px solid #3498db;
            padding-left: 20px;
            margin: 20px 0;
            font-style: italic;
            color: #555;
        }
        
        .contenido code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .contenido pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
        }
        
        /* Estilos para tablas de TiptapEditor */
        .contenido table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
        }
        
        .contenido table th,
        .contenido table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .contenido table th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }
        
        .contenido table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .contenido table tr:hover {
            background-color: #f5f5f5;
        }
        
        /* Estilos para alineación de texto */
        .contenido [style*="text-align: center"] {
            text-align: center !important;
        }
        
        .contenido [style*="text-align: right"] {
            text-align: right !important;
        }
        
        .contenido [style*="text-align: left"] {
            text-align: left !important;
        }
        
        /* Estilos para línea horizontal */
        .contenido hr {
            border: none;
            border-top: 2px solid #ecf0f1;
            margin: 25px 0;
        }
        
        .fecha {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $terminosCondiciones->titulo ?? 'Términos y Condiciones' }}</h1>
        
        <div class="contenido">
            {!! $terminosCondiciones->contenido ?? '<p>No hay términos y condiciones disponibles en este momento.</p>' !!}
        </div>
        
        @if($terminosCondiciones && $terminosCondiciones->updated_at)
        <div class="fecha">
            <strong>Última actualización:</strong> {{ $terminosCondiciones->updated_at->format('d/m/Y') }}
        </div>
        @endif
    </div>
</body>
</html>