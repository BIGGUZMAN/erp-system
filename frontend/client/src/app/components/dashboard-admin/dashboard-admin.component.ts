import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ServicioSocialService } from '../../services/servicio-social.service';

@Component({
  selector: 'app-dashboard-admin',
  standalone: true,
  imports: [CommonModule, HttpClientModule, FormsModule],
  templateUrl: './dashboard-admin.component.html',
  styleUrls: ['./dashboard-admin.component.css']
})
export class DashboardAdminComponent implements OnInit {

  private apiUrl = 'http://localhost:8000/api';

  // Propiedades para el visor de archivos
  urlSegura: SafeResourceUrl | null = null;
  mostrarVisor: boolean = false;
  alumnoSeleccionado: any = null;

  // Propiedades para el registro de nuevos alumnos
  mostrarFormularioRegistro: boolean = false;
  carreras: any[] = [];
  nuevoAlumno = {
    numero_control: '',
    nombre_completo: '',
    correo: '',
    carrera_id: ''
  };

  // Lista base de datos cargada del backend
  alumnosPendientes: any[] = [];

  // Objeto de estadísticas
  public stats: any = {
    totalIngles: 0,
    activeSocialService: 0,
    activeResidencies: 0,
    pendientesRevision: 0
  };

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer,
    private cdr: ChangeDetectorRef,
    private ssService: ServicioSocialService
  ) { }

  ngOnInit(): void {
    this.cargarEstadisticas();
    this.cargarPendientes();
  }

  /**
   * Filtra alumnos con estado 'Entregado' o 'Pendiente' para la lista de revisión
   */
  getPendientes() {
    return this.alumnosPendientes.filter(a =>
      (a.estado === 'Entregado' || a.estado === 'Pendiente') && a.tipo_entidad !== null
    );
  }

  /**
   * Filtra alumnos con estado 'Bloqueado' para la sección de plazos vencidos
   */
  getBloqueados() {
    return this.alumnosPendientes.filter(a => a.estado === 'Bloqueado');
  }

  /**
   * Filtra alumnos con constancia pendiente de generar
   */
  getConstanciasPendientes() {
    return this.alumnosPendientes.filter(a =>
      a.constancia && a.constancia.url === 'PENDIENTE_GENERAR'
    );
  }

  cargarEstadisticas(): void {
    this.http.get<any>(`${this.apiUrl}/dashboard/stats`).subscribe({
      next: (response) => {
        if (response && response.success) {
          this.stats = {
            ...this.stats,
            totalIngles: response.data.totalIngles,
            activeSocialService: response.data.activeSocialService,
            activeResidencies: response.data.activeResidencies
          };
          this.cdr.detectChanges();
        }
      },
      error: (err: any) => console.error('Error al cargar métricas:', err)
    });
  }

  cargarPendientes(): void {
    this.ssService.getAlumnosAdmin().subscribe({
      next: (res: any) => {
        const dataArray = Array.isArray(res) ? res : [];

        this.alumnosPendientes = dataArray
          .filter(item =>
            item.tipo_entidad === 'reporte' ||
            item.tipo_entidad === 'ensayo' ||
            item.estado === 'Bloqueado' ||
            (item.constancia && item.constancia.url === 'PENDIENTE_GENERAR')
          )
          .map(item => ({
            ...item,
            nombreMostrar: item.nombreMostrar || (item.tipo_entidad === 'reporte' ? `REPORTE BIMESTRAL #${item.numero_reporte}` : 'ENSAYO FINAL')
          }));

        // Actualizamos contador de pendientes en tiempo real
        this.stats.pendientesRevision = this.getPendientes().length;
        this.cdr.detectChanges();
      },
      error: (err: any) => console.error('Error al cargar pendientes:', err)
    });
  }

  abrirModalRegistro(): void {
    this.http.get<any>(`${this.apiUrl}/carreras`).subscribe({
      next: (res) => {
        this.carreras = res.carreras;
        this.mostrarFormularioRegistro = true;
        this.cdr.detectChanges();
      },
      error: (err) => console.error('Error al cargar carreras:', err)
    });
  }

  guardarAlumno(): void {
    if (!this.nuevoAlumno.numero_control || !this.nuevoAlumno.correo || !this.nuevoAlumno.carrera_id) {
      alert('Por favor, completa todos los campos obligatorios.');
      return;
    }
    this.http.post(`${this.apiUrl}/servicio-social/admin/registrar-alumno`, this.nuevoAlumno).subscribe({
      next: (res: any) => {
        alert(res.message || 'Alumno registrado correctamente.');
        this.mostrarFormularioRegistro = false;
        this.nuevoAlumno = { numero_control: '', nombre_completo: '', correo: '', carrera_id: '' };
        this.cargarEstadisticas();
      },
      error: (err) => alert(err.error?.message || 'Error al intentar registrar al alumno.')
    });
  }

  desbloquear(pendiente: any): void {
    const fechaSugerida = new Date().toISOString().split('T')[0];
    const nuevaFecha = prompt(
      `Asignar nueva fecha límite para ${pendiente.nombre_completo}:`,
      fechaSugerida
    );

    if (nuevaFecha) {
      this.ssService.desbloquearReporte(pendiente.id_entidad, nuevaFecha).subscribe({
        next: (res: any) => {
          alert(res.message || 'Reporte desbloqueado correctamente.');
          this.cargarPendientes();
          this.cargarEstadisticas();
        },
        error: (err: any) => {
          console.error('Error al desbloquear:', err);
          alert(err.error?.message || 'No se pudo desbloquear el reporte.');
        }
      });
    }
  }

  revisar(pendiente: any): void {
    if (!pendiente.url_archivo) {
      alert('Este registro no tiene un archivo asociado.');
      return;
    }

    let urlFinal = pendiente.url_archivo;
    const currentHost = window.location.hostname;

    if (urlFinal.includes('127.0.0.1') && currentHost === 'localhost') {
      urlFinal = urlFinal.replace('127.0.0.1', 'localhost');
    }

    const separador = urlFinal.includes('?') ? '&' : '?';
    const urlConCache = `${urlFinal}${separador}t=${new Date().getTime()}`;

    window.open(urlConCache, '_blank', 'noopener,noreferrer');
  }

  /**
   * Método para generar la constancia
   */
  generarConstancia(alumno: any): void {
    const id = alumno.id_entidad || alumno.id;
    this.http.post(`${this.apiUrl}/generar-constancia`, { id: id }).subscribe({
      next: (res: any) => {
        alert('Constancia generada con éxito');
        this.cargarPendientes(); // Refrescar para que desaparezca de la lista
      },
      error: (err) => {
        console.error('Error al generar:', err);
        alert('Ocurrió un error al generar la constancia.');
      }
    });
  }

  /**
   * Método principal para validar o rechazar documentos con comentarios
   */
  validar(alumno: any, accion: 'APROBADO' | 'RECHAZADO'): void {
    const idEntidad = alumno.id_entidad || alumno.id;
    const esReporte = alumno.tipo_entidad === 'reporte';

    if (!idEntidad) {
      alert('Error: No se encontró el ID del documento.');
      return;
    }

    let observaciones = 'Documento aprobado correctamente.';

    // Si es rechazo, solicitamos el motivo al usuario
    if (accion === 'RECHAZADO') {
      const motivo = prompt('Ingresa el motivo del rechazo (se mostrará al alumno):', 'El documento no cumple con los requisitos institucionales.');
      if (motivo === null) return; // Si cancela el prompt, no hacemos nada
      observaciones = motivo;
    }

    if (esReporte) {
      this.ssService.validarReporte(idEntidad, accion, observaciones).subscribe({
        next: () => this.finalizarValidacion(accion),
        error: (err) => this.errorValidacion(err)
      });
    } else {
      // Para el ensayo final, enviamos solo idEntidad, acción y observaciones
      this.ssService.validarEnsayo(idEntidad, accion, observaciones).subscribe({
        next: () => {
          this.finalizarValidacion(accion);
        },
        error: (err) => this.errorValidacion(err)
      });
    }
  }

  private finalizarValidacion(accion: string): void {
    alert(`Documento ${accion} con éxito.`);
    this.cargarPendientes();
    this.cargarEstadisticas();
  }

  private errorValidacion(err: any): void {
    console.error('Error en validación:', err);
    alert(err.error?.message || 'Error al procesar la validación.');
  }

  cerrarVisor(): void {
    this.mostrarVisor = false;
    this.urlSegura = null;
    this.alumnoSeleccionado = null;
    this.cdr.detectChanges();
  }
}