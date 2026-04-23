<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 30px; border: 5px double #0a1f44; }
        .header { text-align: center; margin-bottom: 30px; }
        .info-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; color: #0a1f44; width: 40%; }
        .grade-box { margin-top: 40px; text-align: center; border: 2px solid #0a1f44; padding: 20px; }
        .grade { font-size: 30px; font-weight: bold; }
        .footer { margin-top: 100px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d4/Logo-TecNM-2017.png" width="100"><br>
        <h2>BOLETA DE CALIFICACIONES - INGLÉS</h2>
    </div>

    <table class="info-table">
        <tr><td class="label">Alumno:</td><td>{{ $inscripcion->usuario->nombre_completo }}</td></tr>
        <tr><td class="label">Número de Control:</td><td>{{ $inscripcion->usuario->numero_control }}</td></tr>
        <tr><td class="label">Carrera:</td><td>{{ $inscripcion->usuario->carrera->nombre }}</td></tr>
        <tr><td class="label">Nivel Acreditado:</td><td>{{ $inscripcion->nivel->nombre }}</td></tr>
        <tr><td class="label">Ciclo Escolar:</td><td>{{ $inscripcion->ciclo_escolar }}</td></tr>
    </table>

    <div class="grade-box">
        CALIFICACIÓN FINAL: <br>
        <span class="grade">{{ $inscripcion->calificacion_final }}</span><br>
        <strong>({{ strtoupper($inscripcion->estado_academico) }})</strong>
    </div>

    <div class="footer">
        _________________________________<br>
        Departamento de Lenguas Extranjeras ITGAM
    </div>
</body>
</html>