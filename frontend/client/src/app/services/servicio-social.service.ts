import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class ServicioSocialService {
    // La URL base: http://localhost:8000/api/servicio-social
    private apiUrl = `${environment.apiUrl}/servicio-social`;

    constructor(private http: HttpClient) { }

    /**
     * Obtiene la lista unificada de pendientes para el Administrador
     * (reportes EN_REVISION, reportes BLOQUEADO_VENCIDO, ensayos EN_REVISION, cartas pendientes)
     * Ruta: GET /api/servicio-social/admin/pendientes
     */
    getAlumnosAdmin(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/admin/pendientes`);
    }

    /**
     * Obtiene la lista de alumnos que ya completaron todo (historial)
     * Ruta: GET /api/servicio-social/admin/alumnos-completados
     */
    getAlumnosCompletadosAdmin(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/admin/alumnos-completados`);
    }

    /**
     * Obtiene el estado actual del alumno
     * Ruta: GET /api/servicio-social/alumno/estado/{usuarioId}
     */
    getEstado(usuarioId: string): Observable<any> {
        return this.http.get(`${this.apiUrl}/alumno/estado/${usuarioId}`);
    }

    /**
     * Sube los documentos iniciales (Kardex, Constancia, etc.)
     * Ruta: POST /api/servicio-social/alumno/subir-documento
     */
    subirDocumento(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-documento`, formData);
    }

    /**
     * Sube los reportes bimestrales 1, 2 o 3.
     * Ruta: POST /api/servicio-social/alumno/subir-reporte
     */
    subirReporte(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-reporte`, formData);
    }

    /**
     * Sube el Ensayo Final (reutiliza la misma ruta, el backend detecta por tipo_entidad=ensayo)
     */
    subirEnsayoFinal(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-reporte`, formData);
    }

    /**
     * Desbloquea un reporte vencido asignando una nueva fecha límite
     * Ruta: POST /api/servicio-social/admin/desbloquear-reporte
     */
    desbloquearReporte(reporteId: number, nuevaFecha: string): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/desbloquear-reporte`, {
            reporte_id: reporteId,
            nueva_fecha: nuevaFecha
        });
    }

    /**
     * Valida un reporte bimestral (APROBADO/RECHAZADO) con comentarios
     * Ruta: POST /api/servicio-social/admin/validar-reporte
     */
    validarReporte(reporteId: number, accion: 'APROBADO' | 'RECHAZADO', observaciones: string): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/validar-reporte`, {
            reporte_id: reporteId,
            accion: accion,
            observaciones: observaciones
        });
    }

    /**
     * Valida el Ensayo Final (APROBADO/RECHAZADO) con comentarios
     * Ruta: POST /api/servicio-social/admin/validar-documento
     */
    validarEnsayo(documentoId: number, accion: 'APROBADO' | 'RECHAZADO', observaciones: string = ''): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/validar-documento`, {
            documento_id: documentoId,
            accion: accion,
            observaciones: observaciones
        });
    }

    /**
     * Envía la Carta de Término de Servicio Social al alumno.
     * Solo disponible cuando el ensayo final fue aprobado.
     * Ruta: POST /api/servicio-social/admin/enviar-carta
     */
    enviarCarta(data: {
        usuario_id: string;
        nombre_dependencia?: string;
        horas?: number;
    }): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/enviar-carta`, data);
    }

    /**
     * Genera la constancia de liberación para el alumno (Admin)
     * @deprecated Usar enviarCarta() en su lugar
     */
    liberarAlumno(data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/enviar-carta`, data);
    }

    /**
     * Actualiza los logos institucionales (header y/o footer banner)
     * Ruta: POST /api/servicio-social/admin/actualizar-logos
     */
    actualizarLogos(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/actualizar-logos`, formData);
    }
}