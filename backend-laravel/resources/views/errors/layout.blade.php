<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error - PCMATCH')</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .error-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 3rem 2rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        }
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #f1f5f9;
        }
        .error-message {
            font-size: 1rem;
            color: #94a3b8;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .btn-home {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">@yield('code', 'Error')</div>
        <h1 class="error-title">@yield('message', 'Ha ocurrido un error')</h1>
        <p class="error-message">@yield('description', 'Lo sentimos, la solicitud no pudo ser procesada.')</p>
        <a href="/" class="btn-home">Volver al Inicio</a>
    </div>
</body>
</html>
