<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .header { border-bottom: 2px solid #000; margin-bottom: 20px; }
        .content { font-size: 18px; line-height: 1.6; }
        .stamp { margin-top: 50px; font-weight: bold; color: #166534; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO TECNOLÓGICO DE GUSTAVO A. MADERO</h1>
        <h2>Departamento de Inglés</h2>
    </div>
    <div class="content">
        <p>Se extiende la presente constancia a:</p>
        <h3>{{ $inscripcion->usuario->numero_control }} - {{ $inscripcion->usuario->correo }}</h3>
        <p>Por haber acreditado el nivel: <strong>{{ $inscripcion->nivel->nombre }}</strong></p>
        <p>Con una calificación de: <strong>{{ $inscripcion->calificacion_final }}</strong></p>
        <p>Periodo: {{ $inscripcion->ciclo_escolar }}</p>
    </div>
    <div class="stamp">SISTEMA DE CONTROL ESCOLAR - ITGAM</div>
</body>
</html>