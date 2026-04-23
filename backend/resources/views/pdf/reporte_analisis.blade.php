<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0a1f44; padding-bottom: 10px; }
        .stats-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .stat-box { border: 1px solid #ddd; padding: 10px; text-align: center; background: #f4f7f6; }
        .val { font-size: 18px; font-weight: bold; color: #1e40af; }
        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { background: #0a1f44; color: white; padding: 8px; font-size: 11px; }
        .main-table td { border: 1px solid #ddd; padding: 6px; font-size: 10px; text-align: center; }
        .aprobado { color: green; font-weight: bold; }
        .reprobado { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>INSTITUTO TECNOLÓGICO DE GUSTAVO A. MADERO</h2>
        <h3>Reporte de Análisis: {{ $nivel->nombre }} ({{ $stats['modalidad'] }})</h3>
    </div>

    <table class="stats-table">
        <tr>
            <td class="stat-box">Total Alumnos<br><span class="val">{{ $stats['total'] }}</span></td>
            <td class="stat-box">Aprobados<br><span class="val">{{ $stats['aprobados'] }}</span></td>
            <td class="stat-box">Promedio Grupal<br><span class="val">{{ $stats['promedio'] }}</span></td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>No. Control</th>
                <th>Nombre</th>
                <th>Carrera</th>
                <th>Calif.</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $a)
            <tr>
                <td>{{ $a->usuario->numero_control }}</td>
                <td style="text-align: left;">{{ $a->usuario->nombre_completo }}</td>
                <td>{{ $a->usuario->carrera->nombre ?? 'N/A' }}</td>
                <td>{{ $a->calificacion_final ?? '0' }}</td>
                <td class="{{ $a->estado_academico == 'Aprobado' ? 'aprobado' : 'reprobado' }}">
                    {{ $a->estado_academico }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>