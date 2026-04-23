import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { InglesService } from '../../services/ingles-data.service';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

@Component({
    selector: 'app-curso-detalle',
    standalone: true,
    imports: [CommonModule, FormsModule, RouterModule],
    templateUrl: './curso-detalle.component.html',
    styleUrls: ['./curso-detalle.component.css']
})
export class CursoDetalleComponent implements OnInit {
    nivelId!: number;
    nombreCursoActual: string = '';
    alumnos: any[] = [];
    alumnosFiltrados: any[] = [];
    cargando: boolean = true;

    modalidadActual: string = 'Semanal';
    modalidades = ['Semanal', 'Sabatino', 'Intensivo'];

    // NUEVAS VARIABLES PARA GRUPOS
    grupoActual: string = 'A';
    gruposDisponibles = ['A', 'B', 'C'];

    calificacionesGuardadas: boolean = false;

    stats = { promedio: 0, aprobados: 0, reprobados: 0, porcentaje: 0 };

    constructor(
        private route: ActivatedRoute,
        public router: Router,
        private inglesService: InglesService,
        private cdr: ChangeDetectorRef
    ) { }

    ngOnInit(): void {
        this.route.paramMap.subscribe(params => {
            const id = params.get('id');
            if (id) {
                this.nivelId = +id;
                this.nombreCursoActual = this.getNombreCurso(this.nivelId);
                this.cargarAlumnos();
            }
        });
    }

    getNombreCurso(id: number): string {
        if (id <= 5) return `Básico ${id}`;
        if (id >= 6 && id <= 10) return `Intermedio ${id - 5}`;
        if (id >= 11) return `Avanzado ${id - 10}`;
        return `Nivel ${id}`;
    }

    get alumnosPendientes() {
        return this.alumnosFiltrados.filter(a => a.estado_pago?.toLowerCase() === 'pendiente');
    }

    regresar(): void {
        this.router.navigate(['/dashboard-ingles']);
    }

    // MODIFICADO: Ahora carga alumnos enviando el grupo seleccionado
    cargarAlumnos(): void {
        this.cargando = true;
        this.inglesService.getAlumnosPorNivel(this.nivelId, this.grupoActual).subscribe({
            next: (data: any) => {
                this.alumnos = data.alumnos || [];
                this.stats = data.stats || this.stats;
                this.aplicarFiltroInicial();
                this.verificarEstadoGuardado();
                this.cargando = false;
                this.cdr.detectChanges();
            },
            error: (err: any) => {
                this.cargando = false;
                console.error("Error al cargar:", err);
                Swal.fire('Error', 'No se pudieron cargar los alumnos', 'error');
            }
        });
    }

    verificarEstadoGuardado(): void {
        this.calificacionesGuardadas = this.alumnosFiltrados.length > 0 &&
            this.alumnosFiltrados.every(a => a.calificacion_final !== null && a.calificacion_final !== "");
    }

    aplicarFiltroInicial(): void {
        if (this.alumnos.length > 0) {
            this.alumnosFiltrados = this.alumnos.filter(a =>
                a.modalidad?.trim().toLowerCase() === this.modalidadActual.toLowerCase()
            );
        } else {
            this.alumnosFiltrados = [];
        }
        this.verificarEstadoGuardado();
    }

    filtrarPorModalidad(modalidad: string): void {
        this.modalidadActual = modalidad;
        this.aplicarFiltroInicial();
    }

    // NUEVO: Método para cambiar de grupo y recargar datos
    cambiarGrupo(nuevoGrupo: string): void {
        this.grupoActual = nuevoGrupo;
        this.cargarAlumnos();
    }

    subirVoucher(event: any, alumno: any): void {
        const file = event.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('Error', 'El archivo no debe pesar más de 5MB', 'error');
                return;
            }

            Swal.fire({
                title: 'Subiendo comprobante...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            this.inglesService.actualizarPago(alumno.id, file).subscribe({
                next: (res: any) => {
                    const idx = this.alumnos.findIndex(a => a.id === alumno.id);
                    if (idx !== -1) {
                        this.alumnos[idx].estado_pago = 'Pagado';
                        this.alumnos[idx].ruta_comprobante = res.inscripcion.ruta_comprobante;
                    }
                    this.aplicarFiltroInicial();
                    Swal.fire('¡Éxito!', 'Pago registrado correctamente', 'success');
                },
                error: (err: any) => {
                    console.error(err);
                    Swal.fire('Error', 'No se pudo subir el archivo.', 'error');
                }
            });
        }
    }

    exportarExcel(): void {
        const dataParaExcel = this.alumnosFiltrados.map(a => ({
            'Número de Control': a.usuario?.numero_control,
            'Nombre Completo': a.usuario?.nombre_completo,
            'Carrera': a.usuario?.carrera?.nombre || 'General',
            'Modalidad': a.modalidad,
            'Grupo': a.grupo,
            'Pago': a.estado_pago,
            'Calificación': a.calificacion_final,
            'Resultado': a.calificacion_final >= 70 ? 'Aprobado' : 'Reprobado'
        }));

        const worksheet = XLSX.utils.json_to_sheet(dataParaExcel);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Alumnos');

        XLSX.writeFile(workbook, `Respaldo_${this.nombreCursoActual}_${this.modalidadActual}_Grup${this.grupoActual}.xlsx`);
    }

    guardarCalificaciones(): void {
        if (this.alumnosFiltrados.length === 0) return;

        const payload = this.alumnosFiltrados.map(a => ({
            id: a.id,
            calificacion_final: a.calificacion_final
        }));

        this.inglesService.guardarCalificaciones(payload).subscribe({
            next: () => {
                Swal.fire({
                    title: '¡Éxito!',
                    text: `Calificaciones de ${this.modalidadActual} - Grupo ${this.grupoActual} guardadas. ¿Deseas descargar el respaldo en Excel?`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, descargar Excel',
                    cancelButtonText: 'Cerrar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.exportarExcel();
                    }
                    this.cargarAlumnos();
                });
            },
            error: (err: any) => {
                console.error(err);
                Swal.fire('Error', 'No se pudieron guardar las calificaciones', 'error');
            }
        });
    }

    imprimirBoleta(id: number): void {
        this.inglesService.descargarBoletaIndividual(id).subscribe({
            next: (blob: Blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Boleta_Alumno.pdf`;
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: (err: any) => {
                console.error(err);
                Swal.fire('Error', 'No se pudo generar la boleta individual', 'error');
            }
        });
    }

    vaciarCurso(): void {
        if (this.alumnosFiltrados.length === 0) {
            Swal.fire('Atención', 'No hay alumnos para vaciar en esta modalidad/grupo.', 'info');
            return;
        }

        Swal.fire({
            title: `¿Cerrar Ciclo ${this.modalidadActual} - Grupo ${this.grupoActual}?`,
            html: `Se borrarán las inscripciones de esta modalidad y grupo. <br><b>Asegúrate de haber descargado el Excel y el Reporte de Análisis.</b>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, vaciar tabla'
        }).then((result) => {
            if (result.isConfirmed) {
                this.inglesService.vaciarCurso(this.nivelId, this.modalidadActual, this.grupoActual).subscribe({
                    next: () => {
                        Swal.fire('Vaciado', `La tabla de ${this.modalidadActual} Grupo ${this.grupoActual} ha sido limpiada.`, 'success');
                        this.cargarAlumnos();
                    },
                    error: (err: any) => {
                        console.error(err);
                        Swal.fire('Error', 'No se pudo vaciar el curso', 'error');
                    }
                });
            }
        });
    }

    generarReporte(): void {
        const modalidad = this.modalidadActual;
        const grupo = this.grupoActual;
        this.inglesService.descargarReporte(this.nivelId, modalidad, grupo).subscribe({
            next: (blob: Blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Analisis_${this.nombreCursoActual}_${modalidad}_Grup${grupo}.pdf`;
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: (err: any) => {
                console.error(err);
                Swal.fire('Error', 'No se pudo generar el Reporte de Análisis', 'error');
            }
        });
    }
}