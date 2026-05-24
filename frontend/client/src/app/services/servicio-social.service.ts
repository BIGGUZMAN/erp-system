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
     * Obtiene la lista de alumnos pendientes y bloqueados para el Administrador
     * Ruta en Laravel: GET /api/servicio-social/admin/pendientes
     */
    getAlumnosAdmin(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/admin/pendientes`);
    }

    /**
     * Obtiene la lista de alumnos que ya completaron todo para el Administrador
     * Ruta en Laravel: GET /api/servicio-social/admin/alumnos-completados
     */
    getAlumnosCompletadosAdmin(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/admin/alumnos-completados`);
    }

    /**
     * Obtiene el estado actual del alumno (Documentos iniciales y Reportes)
     * Ruta en Laravel: GET /api/servicio-social/alumno/estado/{usuarioId}
     */
    getEstado(usuarioId: string): Observable<any> {
        const urlFinal = `${this.apiUrl}/alumno/estado/${usuarioId}`;
        console.log('DEBUG: ServicioSocialService llamando a:', urlFinal);
        return this.http.get(urlFinal);
    }

    /**
     * Sube los documentos de la fase inicial (Kardex, Constancia, etc.)
     * Ruta en Laravel: POST /api/servicio-social/alumno/subir-documento
     */
    subirDocumento(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-documento`, formData);
    }

    /**
     * Sube los reportes bimestrales 1, 2 o 3. 
     * Ruta en Laravel: POST /api/servicio-social/alumno/subir-reporte
     */
    subirReporte(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-reporte`, formData);
    }

    /**
     * Sube el Ensayo Final
     * Reutiliza la ruta de subir-reporte pero el backend lo detecta por la falta de reporte_id
     */
    subirEnsayoFinal(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/alumno/subir-reporte`, formData);
    }

    /**
     * Desbloquea un reporte vencido asignando una nueva fecha límite
     * Ruta en Laravel: POST /api/servicio-social/admin/desbloquear-reporte
     */
    desbloquearReporte(reporteId: number, nuevaFecha: string): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/desbloquear-reporte`, {
            reporte_id: reporteId,
            nueva_fecha: nuevaFecha
        });
    }

    /**
     * Valida un reporte bimestral (APROBADO/RECHAZADO) con comentarios
     * Ruta en Laravel: POST /api/servicio-social/admin/validar-reporte
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
     * Se sincroniza con la lógica del dashboard-admin
     */
    validarEnsayo(documentoId: number, accion: 'APROBADO' | 'RECHAZADO', observaciones: string = ''): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/validar-documento`, {
            documento_id: documentoId,
            accion: accion,
            observaciones: observaciones
        });
    }

    /**
     * Genera la constancia de liberación para el alumno (Admin)
     * Ruta en Laravel: POST /api/servicio-social/admin/liberar-alumno
     */
    liberarAlumno(data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/liberar-alumno`, data);
    }
}