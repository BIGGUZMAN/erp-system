import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map, catchError, of, BehaviorSubject, tap } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class InglesService {
    private apiUrl = `${environment.apiUrl}/ingles`;
    private generalApiUrl = `${environment.apiUrl}`;

    // Subject para emitir el estado global y que todos los componentes se actualicen al unísono
    private estadoSubject = new BehaviorSubject<any>(null);
    public estado$ = this.estadoSubject.asObservable();

    constructor(private http: HttpClient) { }

    /**
     * Permite a los componentes obtener el último valor emitido por el Subject
     * de forma síncrona, útil cuando se navega entre pestañas (inicio -> inglés).
     */
    obtenerEstadoActual() {
        return this.estadoSubject.value;
    }

    getDashboardData(): Observable<any> {
        return this.http.get(`${this.apiUrl}/dashboard`);
    }

    buscarAlumnoPorControl(nc: string): Observable<any> {
        return this.http.get<any>(`${this.apiUrl}/buscar-alumno/${nc}`).pipe(
            catchError(error => {
                console.error('Error al buscar alumno:', error);
                return of(null);
            })
        );
    }

    getCarreras(): Observable<any[]> {
        return this.http.get<any>(`${this.generalApiUrl}/carreras`).pipe(
            map(res => {
                if (res && res.carreras) return res.carreras;
                return Array.isArray(res) ? res : [];
            }),
            catchError(() => of([]))
        );
    }

    getNiveles(): Observable<any[]> {
        return this.http.get<any>(`${this.apiUrl}/niveles`).pipe(
            map(res => {
                if (res && res.niveles) return res.niveles;
                return Array.isArray(res) ? res : [];
            }),
            catchError(() => of([]))
        );
    }

    inscribirAlumno(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/inscribir`, formData);
    }

    actualizarPago(id: number, file: File): Observable<any> {
        const formData = new FormData();
        formData.append('comprobante', file);
        return this.http.post(`${this.apiUrl}/actualizar-pago/${id}`, formData);
    }

    descargarBoletaIndividual(id: number): Observable<Blob> {
        return this.http.get(`${this.apiUrl}/boleta/${id}`, { responseType: 'blob' });
    }

    getAlumnosPorNivel(nivelId: number, grupo: string = 'A'): Observable<any> {
        return this.http.get(`${this.apiUrl}/curso/${nivelId}/alumnos`, {
            params: { grupo: grupo }
        });
    }

    guardarCalificaciones(calificaciones: any[]): Observable<any> {
        return this.http.post(`${this.apiUrl}/calificaciones`, { calificaciones });
    }

    vaciarCurso(nivelId: number, modalidad: string, grupo: string = 'A'): Observable<any> {
        return this.http.delete(`${this.apiUrl}/curso/${nivelId}/vaciar`, {
            params: { modalidad: modalidad, grupo: grupo }
        });
    }

    descargarReporte(nivelId: number, modalidad: string, grupo: string = 'A'): Observable<Blob> {
        return this.http.get(`${this.apiUrl}/reporte-nivel/${nivelId}`, {
            params: { modalidad: modalidad, grupo: grupo },
            responseType: 'blob'
        });
    }

    /**
     * Obtiene el progreso real del alumno y notifica a los suscriptores.
     * Esta función es el motor que actualiza tanto el Dashboard como la Sección de Inglés.
     */
    getMiEstadoActual(usuarioId: number): Observable<any> {
        // Realiza la petición HTTP que observamos en el Network
        return this.http.get(`${this.apiUrl}/alumno-estado/${usuarioId}`).pipe(
            tap(res => {
                // Log para verificar que los datos llegaron de la base de datos
                console.log('Datos reales recibidos de la API (Service):', res);
                if (res) {
                    // Notifica a todos los componentes que están "escuchando" el estado$
                    this.estadoSubject.next(res);
                }
            }),
            catchError(error => {
                console.error('Error crítico al obtener estado de inglés:', error);
                const errorState = {
                    historial: [],
                    porcentaje_total: 0,
                    conteo_aprobados: 0,
                    nivel_siguiente: 1
                };
                this.estadoSubject.next(errorState);
                return of(errorState);
            })
        );
    }
}