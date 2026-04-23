<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Nivel</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f4f4f4; border: 1px solid #ddd; padding: 10px; }
        td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .footer { margin-top: 30px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE ALUMNOS</h1>
        <h2>Nivel: {{ $nivel->nombre }} - {{ $stats['modalidad'] }}</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Control</th>
                <th>Nombre</th>
                <th>Calificación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            <tr>
                <td>{{ $alumno->usuario->numero_control ?? 'S/N' }}</td>
                <td>{{ $alumno->usuario->name }}</td>
                <td>{{ $alumno->calificacion_final }}</td>
                <td>{{ $alumno->estado_academico }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Promedio Grupal: {{ $stats['promedio'] }}</p>
        <p>Total de Alumnos: {{ $stats['total'] }}</p>
    </div>
</body>
</html>