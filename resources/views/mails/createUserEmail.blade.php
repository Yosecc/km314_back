<!DOCTYPE html>
<html>
<head>
    <title>Nuevo Usuario Creado</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f6f6f6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 120px;
        }
        .content {
            margin-bottom: 20px;
        }
        .content h1 {
            color: #e74c3c;
            font-size: 24px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
        }
        .content a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .content a:hover {
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #95a5a6;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://admin.kilometro314.com/images/logo.png" alt="Logo">
        </div>
        <div class="content">
            <h1>¡Bienvenido!</h1>
            <p>Hola,</p>
            <p>Nos complace informarte que tu usuario y contraseña han sido creados exitosamente.</p>
            <p>Este es tu usuario:</p>
            <p style="font-size: 20px; text-align: center; font-weight: bold;">{{ $this->user->email }}</p>
            <p>Y esta es tu contraseña:</p>
            <p style="font-size: 20px; text-align: center; font-weight: bold;">{{ $this->password }}</p>
            <p>Por favor, visita el siguiente enlace para acceder a tu cuenta:</p>
            <p><a href="https://admin.kilometro314.com/">https://admin.kilometro314.com/</a></p>
            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
            <p>¡Gracias!</p>
            <p>Este es un mensaje generado automáticamente, por favor no responda a este correo.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Kilometro 314. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
