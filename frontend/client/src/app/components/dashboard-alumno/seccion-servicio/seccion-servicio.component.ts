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
  documentos: any[] = [];
  reportes: any[] = [];
  ensayoFinal: any = null;
  constanciaLiberacion: any = null;

  selectedDocNombre: string = '';
  selectedReporteId: number = 0;
  urlSegura: SafeResourceUrl | null = null;
  mostrarVisor: boolean = false;

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
    this.timerInterval = setInterval(() => {
      this.actualizarCuentaRegresiva();
    }, 1000);
  }

  ngOnDestroy(): void {
    if (this.timerInterval) clearInterval(this.timerInterval);
  }

  private normalizar(texto: string): string {
    return texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
  }

  cargarEstado() {
    this.ssService.getEstado(this.usuarioId).subscribe({
      next: (res: any) => {
        const nombresObligatorios = [
          'Kardex', 'Carta de presentacion', 'Carta de Aceptación',
          'Solicitud de servicio social', 'Carta compromiso de servicio social',
          'Asignacion de actividades del servicio social'
        ];

        const docsSource = res.documentacion || res.documentos || [];
        this.documentos = nombresObligatorios.map(nombreObligatorio => {
          const docExistente = docsSource.find((d: any) =>
            this.normalizar(d.tipo_documento || d.nombre || '') === this.normalizar(nombreObligatorio)
          );
          return {
            ...(docExistente || {}),
            nombre: nombreObligatorio,
            statusValidacion: docExistente?.estado || 'INACTIVO',
            observaciones: (docExistente?.comentarios_admin || '').trim(),
            url_final: docExistente?.ruta_archivo || null,
            editando: false
          };
        });

        this.ensayoFinal = res.ensayo ? { ...res.ensayo, nombre: 'Ensayo Final', statusValidacion: res.ensayo.estado || 'INACTIVO', observaciones: (res.ensayo.comentarios_admin || '').trim(), url_final: res.ensayo.ruta_archivo || null, editando: false } : null;
        this.reportes = (res.reportes || []).map((rep: any) => ({ ...rep, statusValidacion: rep.estado || 'INACTIVO', observaciones: (rep.comentarios_admin || '').trim(), tiempoRestante: '', url_final: rep.ruta_archivo, puedeSubir: rep.estado === 'ACTIVO' || rep.estado === 'RECHAZADO' }));
        this.constanciaLiberacion = res.constancia || null;
        this.actualizarCuentaRegresiva();
        this.cdr.detectChanges();
      }
    });
  }

  actualizarCuentaRegresiva() {
    const ahora = new Date().getTime();
    this.reportes.forEach(rep => {
      if (['APROBADO', 'EN_REVISION'].includes(rep.statusValidacion)) {
        rep.tiempoRestante = rep.statusValidacion === 'APROBADO' ? 'Aprobado' : 'En Revisión';
      } else if (rep.statusValidacion === 'ACTIVO' && rep.fecha_limite) {
        const diff = new Date(rep.fecha_limite).getTime() - ahora;
        rep.tiempoRestante = diff <= 0 ? '¡PLAZO VENCIDO!' : `${Math.floor(diff / (1000 * 60 * 60 * 24))}d ${Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))}h`;
      }
    });
  }

  // --- MÉTODOS QUE FALTABAN ---
  calcularProgreso(): number {
    return this.documentos.filter(d => d.statusValidacion === 'APROBADO').length;
  }

  habilitarEdicionDocumento(doc: any) {
    doc.editando = true;
  }

  puedoSubirEnsayo(): boolean {
    return this.reportes.length === 3 && this.reportes.every(r => r.statusValidacion === 'APROBADO') && (!this.ensayoFinal || this.ensayoFinal.statusValidacion === 'RECHAZADO');
  }

  triggerReporteUpload(id: number, nombre: string, input: HTMLInputElement) {
    this.selectedReporteId = id;
    this.selectedDocNombre = nombre;
    input.click();
  }

  imprimirMiConstancia() {
    if (this.constanciaLiberacion) {
      const ventana = window.open('', '_blank');
      ventana?.document.write(this.constanciaLiberacion.ruta_archivo);
      ventana?.document.close();
    }
  }

  // --- MÉTODOS DE SUBIDA ---
  subirReporte(event: any, reporteId: number) {
    const file = event.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('usuario_id', this.usuarioId);
    formData.append('tipo_entidad', this.selectedDocNombre === 'Ensayo Final' ? 'ensayo' : 'reporte');
    if (this.selectedDocNombre !== 'Ensayo Final') formData.append('reporte_id', reporteId.toString());

    this.ssService.subirReporte(formData).subscribe(() => { this.cargarEstado(); });
  }

  onFileSelected(event: any, nombre: string) {
    const file = event.target.files[0];
    if (file) {
      const formData = new FormData();
      formData.append('archivo', file);
      formData.append('tipo_documento', nombre);
      formData.append('usuario_id', this.usuarioId);
      this.ssService.subirDocumento(formData).subscribe(() => { this.cargarEstado(); });
    }
  }

  triggerDocUpload(nombre: string, input: HTMLInputElement) {
    this.selectedDocNombre = nombre;
    input.click();
  }

  revisarEntrega(item: any) {
    let url = item.url_final || item.ruta_archivo;
    if (url) window.open(url.startsWith('http') ? url : `http://localhost:8000/api/servicio-social/ver-archivo?ruta=${encodeURIComponent(url)}`, '_blank');
  }
}