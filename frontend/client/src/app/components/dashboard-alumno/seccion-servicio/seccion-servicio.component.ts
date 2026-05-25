import { Component, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ServicioSocialService } from '../../../services/servicio-social.service';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

@Component({
  selector: 'app-seccion-servicio',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './seccion-servicio.component.html',
  styleUrls: ['./seccion-servicio.component.css']
})
export class SeccionServicioComponent implements OnInit, OnDestroy {
  usuarioId: string = '';

  /** Lista de 6 documentos iniciales */
  documentos: any[] = [];

  /** Reportes bimestrales R1, R2, R3 */
  reportes: any[] = [];

  /** Ensayo final */
  ensayoFinal: any = null;

  /** Constancia de Liberación (carta de término) */
  constanciaLiberacion: any = null;

  selectedDocNombre: string = '';
  selectedReporteId: number = 0;
  urlSegura: SafeResourceUrl | null = null;
  mostrarVisor: boolean = false;

  /** Mensaje de éxito/error para subida de documentos */
  mensajeSubida: string = '';
  tipoMensaje: 'success' | 'error' | '' = '';

  private timerInterval: any;

  constructor(
    private ssService: ServicioSocialService,
    private sanitizer: DomSanitizer,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit(): void {
    const userStr = localStorage.getItem('usuario');
    if (userStr) {
      try {
        const user = JSON.parse(userStr);
        this.usuarioId = user.numero_control || user.id_usuario || user.id || user.user_id || '';
      } catch (e) {
        console.error('Error al parsear el usuario', e);
      }
    }
    if (this.usuarioId) {
      this.cargarEstado();
    }
    // Actualizar cuenta regresiva cada segundo
    this.timerInterval = setInterval(() => {
      this.actualizarCuentaRegresiva();
    }, 1000);
  }

  ngOnDestroy(): void {
    if (this.timerInterval) clearInterval(this.timerInterval);
  }

  private normalizar(texto: string): string {
    return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  // ─────────────────────────────────────────────
  // CARGAR ESTADO DESDE EL BACKEND
  // ─────────────────────────────────────────────
  cargarEstado() {
    this.ssService.getEstado(this.usuarioId).subscribe({
      next: (res: any) => {
        const nombresObligatorios = [
          'Kardex',
          'Carta de presentacion',
          'Carta de aceptacion',
          'Solicitud de servicio social',
          'Carta compromiso de servicio social',
          'Asignacion de actividades del servicio social'
        ];

        const docsSource = res.documentacion || res.documentos || [];

        // Mapear los 6 documentos obligatorios
        this.documentos = nombresObligatorios.map(nombreObligatorio => {
          const docExistente = docsSource.find((d: any) =>
            this.normalizar(d.tipo_documento || d.nombre || '') === this.normalizar(nombreObligatorio)
          );
          return {
            ...(docExistente || {}),
            nombre: nombreObligatorio,
            // Estado: 'Cargado' si se subió, 'INACTIVO' si no
            statusValidacion: docExistente?.estado || 'INACTIVO',
            url_final: docExistente?.ruta_archivo || null,
          };
        });

        // Mapear reportes bimestrales
        this.reportes = (res.reportes || []).map((rep: any) => ({
          ...rep,
          statusValidacion: rep.estado || 'INACTIVO',
          observaciones: (rep.comentarios_admin || '').trim(),
          tiempoRestante: '',
          url_final: rep.ruta_archivo || null,
          // Puede subir si está ACTIVO o RECHAZADO
          puedeSubir: rep.estado === 'ACTIVO' || rep.estado === 'RECHAZADO'
        }));

        // Ensayo final
        if (res.ensayo) {
          this.ensayoFinal = {
            ...res.ensayo,
            nombre: 'Ensayo Final',
            statusValidacion: res.ensayo.estado || 'INACTIVO',
            observaciones: (res.ensayo.comentarios_admin || '').trim(),
            url_final: res.ensayo.ruta_archivo || null
          };
        } else {
          this.ensayoFinal = null;
        }

        // Constancia de liberación (carta de término)
        this.constanciaLiberacion = res.constancia || null;

        this.actualizarCuentaRegresiva();
        this.cdr.detectChanges();
      },
      error: (err) => console.error('Error al cargar estado de servicio social:', err)
    });
  }

  // ─────────────────────────────────────────────
  // CUENTA REGRESIVA EN REPORTES
  // ─────────────────────────────────────────────
  actualizarCuentaRegresiva() {
    const ahora = new Date().getTime();
    this.reportes.forEach(rep => {
      if (rep.statusValidacion === 'APROBADO') {
        rep.tiempoRestante = '✓ Aprobado';
      } else if (rep.statusValidacion === 'EN_REVISION') {
        rep.tiempoRestante = '⏳ En revisión';
      } else if (rep.statusValidacion === 'BLOQUEADO_VENCIDO') {
        rep.tiempoRestante = '🔒 Bloqueado';
      } else if (rep.statusValidacion === 'RECHAZADO') {
        rep.tiempoRestante = '✗ Rechazado';
      } else if (rep.statusValidacion === 'INACTIVO') {
        rep.tiempoRestante = '— Inactivo';
      } else if (rep.statusValidacion === 'ACTIVO' && rep.fecha_limite) {
        const diff = new Date(rep.fecha_limite).getTime() - ahora;
        if (diff <= 0) {
          rep.tiempoRestante = '¡PLAZO VENCIDO!';
        } else {
          const dias  = Math.floor(diff / (1000 * 60 * 60 * 24));
          const horas = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          const mins  = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
          rep.tiempoRestante = `${dias}d ${horas}h ${mins}m`;
        }
      }
    });
  }

  // ─────────────────────────────────────────────
  // PROGRESO: contar documentos con archivo subido
  // Los documentos iniciales NO se validan, solo se suben.
  // ─────────────────────────────────────────────
  calcularProgreso(): number {
    return this.documentos.filter(d => !!d.url_final).length;
  }

  cicloActivado(): boolean {
    return this.calcularProgreso() >= 6;
  }

  // ─────────────────────────────────────────────
  // CONDICIONES DE SUBIDA
  // ─────────────────────────────────────────────
  puedoSubirEnsayo(): boolean {
    // Solo si los 3 reportes están aprobados y no hay ensayo en revisión/aprobado
    const todoAprobado = this.reportes.length === 3 &&
      this.reportes.every(r => r.statusValidacion === 'APROBADO');
    const sinEnsayoActivo = !this.ensayoFinal ||
      this.ensayoFinal.statusValidacion === 'RECHAZADO';
    return todoAprobado && sinEnsayoActivo;
  }

  // ─────────────────────────────────────────────
  // TRIGGER DE INPUTS DE ARCHIVO
  // ─────────────────────────────────────────────
  triggerDocUpload(nombre: string, input: HTMLInputElement) {
    this.selectedDocNombre = nombre;
    input.click();
  }

  triggerReporteUpload(id: number, nombre: string, input: HTMLInputElement) {
    this.selectedReporteId = id;
    this.selectedDocNombre = nombre;
    input.click();
  }

  // ─────────────────────────────────────────────
  // SUBIDA DE DOCUMENTO INICIAL
  // ─────────────────────────────────────────────
  onFileSelected(event: any, nombre: string) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('tipo_documento', nombre);
    formData.append('usuario_id', this.usuarioId);

    this.ssService.subirDocumento(formData).subscribe({
      next: (res: any) => {
        this.mostrarMensaje(`✅ "${nombre}" subido correctamente.`, 'success');
        this.cargarEstado();
      },
      error: (err: any) => {
        this.mostrarMensaje(`❌ Error al subir "${nombre}": ${err.error?.message || 'Inténtalo de nuevo.'}`, 'error');
      }
    });
  }

  // ─────────────────────────────────────────────
  // SUBIDA DE REPORTE BIMESTRAL O ENSAYO
  // ─────────────────────────────────────────────
  subirReporte(event: any, reporteId: number) {
    const file = event.target.files[0];
    if (!file) return;

    const esEnsayo = this.selectedDocNombre === 'Ensayo Final';
    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('usuario_id', this.usuarioId);
    formData.append('tipo_entidad', esEnsayo ? 'ensayo' : 'reporte');
    if (!esEnsayo) {
      formData.append('reporte_id', reporteId.toString());
    }

    this.ssService.subirReporte(formData).subscribe({
      next: (res: any) => {
        const nombre = esEnsayo ? 'Ensayo Final' : `Reporte ${this.selectedDocNombre}`;
        this.mostrarMensaje(`✅ ${nombre} entregado. Pendiente de revisión.`, 'success');
        this.cargarEstado();
      },
      error: (err: any) => {
        this.mostrarMensaje(`❌ ${err.error?.error || 'Error al subir el archivo.'}`, 'error');
      }
    });
  }

  // ─────────────────────────────────────────────
  // IMPRIMIR CONSTANCIA
  // ─────────────────────────────────────────────
  imprimirMiConstancia() {
    if (this.constanciaLiberacion?.ruta_archivo) {
      const ventana = window.open('', '_blank');
      ventana?.document.write(this.constanciaLiberacion.ruta_archivo);
      ventana?.document.close();
      setTimeout(() => ventana?.print(), 800);
    }
  }

  // ─────────────────────────────────────────────
  // VER ARCHIVO SUBIDO
  // ─────────────────────────────────────────────
  revisarEntrega(item: any) {
    let url = item.url_final || item.ruta_archivo;
    if (!url) return;
    const urlFinal = url.startsWith('http')
      ? url
      : `http://localhost:8000/api/servicio-social/ver-archivo?ruta=${encodeURIComponent(url)}`;
    window.open(urlFinal, '_blank');
  }

  // ─────────────────────────────────────────────
  // HELPERS DE INTERFAZ
  // ─────────────────────────────────────────────
  private mostrarMensaje(texto: string, tipo: 'success' | 'error') {
    this.mensajeSubida = texto;
    this.tipoMensaje = tipo;
    setTimeout(() => {
      this.mensajeSubida = '';
      this.tipoMensaje = '';
      this.cdr.detectChanges();
    }, 5000);
    this.cdr.detectChanges();
  }

  getEtiquetaEstado(estado: string): string {
    const etiquetas: { [key: string]: string } = {
      'ACTIVO': 'Activo',
      'INACTIVO': 'Inactivo',
      'EN_REVISION': 'En Revisión',
      'APROBADO': 'Aprobado',
      'RECHAZADO': 'Rechazado',
      'BLOQUEADO_VENCIDO': 'Bloqueado',
      'Cargado': 'Subido',
    };
    return etiquetas[estado] || estado;
  }
}