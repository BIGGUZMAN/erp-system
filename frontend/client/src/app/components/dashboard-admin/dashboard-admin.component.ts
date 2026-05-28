import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ServicioSocialService } from '../../services/servicio-social.service';
import { Router } from '@angular/router';

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

  anioActual: number = new Date().getFullYear();

  // Modal para enviar carta
  mostrarModalCarta: boolean = false;
  itemCartaActual: any = null;
  formCarta = {
    nombre_dependencia: '',
    horas: 480,
    folio_num: ''
  };

  // Lista unificada del backend
  alumnosPendientes: any[] = [];

  // --- MÓDULO DE CONVENIOS CON EMPRESAS ---
  activeTab: string = 'validaciones';
  empresasList: any[] = [];
  searchQuery: string = '';
  filterEstatus: string = '';
  filterTipoConvenio: string = '';
  mostrarModalEmpresa: boolean = false;
  editandoEmpresa: boolean = false;
  empresaIdEnEdicion: number | null = null;
  cargandoEmpresas: boolean = false;
  errorEmpresas: string | null = null;

  formEmpresa: any = {
    anio: new Date().getFullYear(),
    empresa: '',
    tipo_empresa: 'Privada',
    rfc: '',
    direccion: '',
    tipo_convenio: 'Servicio Social',
    fecha_firma: '',
    vigencia: 3,
    fecha_termino: '',
    convenio_fisico: 'No',
    representante: '',
    cargo: '',
    contacto: '',
    telefono: '',
    correo: '',
    igem: false,
    itics: false,
    ilog: false,
    ind: false,
    idam: false,
    ife: false,
    proyectos: '',
    comentarios: ''
  };

  mostrarModalRenovacion: boolean = false;
  empresaIdRenovacion: number | null = null;
  empresaNombreRenovacion: string = '';
  formRenovacion = {
    nueva_fecha_firma: '',
    nueva_vigencia: 3,
    nueva_fecha_termino: '',
    comentarios: ''
  };

  mostrarModalHistorial: boolean = false;
  empresaNombreHistorial: string = '';
  historialRenovaciones: any[] = [];

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
    private ssService: ServicioSocialService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.cargarEstadisticas();
    this.cargarPendientes();
  }

  cambiarTab(tab: string): void {
    this.activeTab = tab;
    if (tab === 'convenios') {
      this.cargarEmpresas();
    }
  }

  cerrarSesion(): void {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    localStorage.removeItem('tipo_usuario');
    this.router.navigate(['/login']);
  }

  // ─────────────────────────────────────────────
  // FILTROS DE SECCIONES
  // ─────────────────────────────────────────────

  /** Reportes y ensayos EN_REVISION → sección "Por Validar" */
  getPendientes() {
    return this.alumnosPendientes.filter(a =>
      a.estado === 'Entregado' && (a.tipo_entidad === 'reporte' || a.tipo_entidad === 'ensayo')
    );
  }

  /** Reportes BLOQUEADO_VENCIDO → sección "Reportes Bloqueados" */
  getBloqueados() {
    return this.alumnosPendientes.filter(a => a.estado === 'Bloqueado');
  }

  /** Ensayos aprobados sin carta generada → sección "Cartas de Término" */
  getCartasPendientes() {
    return this.alumnosPendientes.filter(a => a.tipo_entidad === 'carta_pendiente');
  }

  // ─────────────────────────────────────────────
  // CARGA DE DATOS
  // ─────────────────────────────────────────────

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
        this.alumnosPendientes = Array.isArray(res) ? res : [];
        this.stats.pendientesRevision = this.getPendientes().length;
        this.cdr.detectChanges();
      },
      error: (err: any) => console.error('Error al cargar pendientes:', err)
    });
  }

  // ─────────────────────────────────────────────
  // REGISTRO DE ALUMNO
  // ─────────────────────────────────────────────

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

  // ─────────────────────────────────────────────
  // DESBLOQUEAR REPORTE VENCIDO
  // ─────────────────────────────────────────────

  desbloquear(pendiente: any): void {
    const hoy = new Date();
    hoy.setDate(hoy.getDate() + 14); // Sugerir 2 semanas desde hoy
    const fechaSugerida = hoy.toISOString().split('T')[0];

    const nuevaFecha = prompt(
      `Asignar nueva fecha límite para ${pendiente.nombre_completo}\n(Reporte #${pendiente.numero_reporte}):`,
      fechaSugerida
    );

    if (!nuevaFecha) return;

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

  // ─────────────────────────────────────────────
  // REVISAR ARCHIVO
  // ─────────────────────────────────────────────

  revisar(pendiente: any): void {
    if (!pendiente.url_archivo) {
      alert('Este registro no tiene un archivo asociado.');
      return;
    }
    let urlFinal = pendiente.url_archivo;
    if (urlFinal.includes('127.0.0.1') && window.location.hostname === 'localhost') {
      urlFinal = urlFinal.replace('127.0.0.1', 'localhost');
    }
    const urlConCache = `${urlFinal}${urlFinal.includes('?') ? '&' : '?'}t=${Date.now()}`;
    window.open(urlConCache, '_blank', 'noopener,noreferrer');
  }

  // ─────────────────────────────────────────────
  // VALIDAR REPORTE O ENSAYO
  // ─────────────────────────────────────────────

  validar(alumno: any, accion: 'APROBADO' | 'RECHAZADO'): void {
    const idEntidad = alumno.id_entidad;
    const esReporte = alumno.tipo_entidad === 'reporte';

    if (!idEntidad) {
      alert('Error: No se encontró el ID del documento.');
      return;
    }

    let observaciones = 'Documento aprobado correctamente.';

    if (accion === 'RECHAZADO') {
      const motivo = prompt(
        'Ingresa el motivo del rechazo (se mostrará al alumno):',
        'El documento no cumple con los requisitos institucionales.'
      );
      if (motivo === null) return; // Canceló
      observaciones = motivo;
    }

    if (esReporte) {
      this.ssService.validarReporte(idEntidad, accion, observaciones).subscribe({
        next: () => this.finalizarValidacion(accion),
        error: (err) => this.errorValidacion(err)
      });
    } else {
      // Ensayo final
      this.ssService.validarEnsayo(idEntidad, accion, observaciones).subscribe({
        next: () => {
          if (accion === 'APROBADO') {
            alert('✅ Ensayo Final aprobado. El alumno aparecerá en "Cartas de Término" para que le envíes la constancia.');
          } else {
            alert('❌ Ensayo rechazado. El alumno podrá corregirlo y volver a subirlo.');
          }
          this.cargarPendientes();
          this.cargarEstadisticas();
        },
        error: (err) => this.errorValidacion(err)
      });
    }
  }

  // ─────────────────────────────────────────────
  // ENVIAR CARTA DE TÉRMINO
  // ─────────────────────────────────────────────

  abrirModalCarta(item: any): void {
    this.itemCartaActual = item;
    const randomFolio = Math.floor(1 + Math.random() * 9999).toString().padStart(4, '0');
    this.formCarta = { nombre_dependencia: '', horas: 480, folio_num: randomFolio };
    this.mostrarModalCarta = true;
    this.cdr.detectChanges();
  }

  cerrarModalCarta(): void {
    this.mostrarModalCarta = false;
    this.itemCartaActual = null;
    this.cdr.detectChanges();
  }

  confirmarEnviarCarta(): void {
    if (!this.itemCartaActual) return;

    const usuarioId = this.itemCartaActual.usuario_id || this.itemCartaActual.numero_control;

    this.ssService.enviarCarta({
      usuario_id: usuarioId,
      nombre_dependencia: this.formCarta.nombre_dependencia,
      horas: this.formCarta.horas,
      folio_num: this.formCarta.folio_num
    }).subscribe({
      next: (res: any) => {
        alert(`✅ ${res.message || 'Carta de término enviada correctamente.'}`);
        this.cerrarModalCarta();
        this.cargarPendientes();
      },
      error: (err: any) => {
        console.error('Error al enviar carta:', err);
        alert(err.error?.error || 'Error al generar la carta de término.');
      }
    });
  }

  // ─────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────

  private finalizarValidacion(accion: string): void {
    alert(`Documento ${accion === 'APROBADO' ? '✅ aprobado' : '❌ rechazado'} correctamente.`);
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

  // ─────────────────────────────────────────────
  // ACTUALIZAR LOGOS INSTITUCIONALES
  // ─────────────────────────────────────────────

  mostrarModalLogos: boolean = false;
  headerFile: File | null = null;
  footerFile: File | null = null;
  headerPreview: string | null = null;
  footerPreview: string | null = null;
  subiendoLogos: boolean = false;

  abrirModalLogos(): void {
    this.headerFile = null;
    this.footerFile = null;
    this.headerPreview = null;
    this.footerPreview = null;
    this.subiendoLogos = false;
    this.mostrarModalLogos = true;
    this.cdr.detectChanges();
  }

  cerrarModalLogos(): void {
    this.mostrarModalLogos = false;
    this.headerFile = null;
    this.footerFile = null;
    this.headerPreview = null;
    this.footerPreview = null;
    this.cdr.detectChanges();
  }

  onHeaderSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      this.headerFile = input.files[0];
      this.generarPreview(this.headerFile, 'header');
    }
  }

  onFooterSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      this.footerFile = input.files[0];
      this.generarPreview(this.footerFile, 'footer');
    }
  }

  onDropHeader(event: DragEvent): void {
    event.preventDefault();
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
      this.headerFile = event.dataTransfer.files[0];
      this.generarPreview(this.headerFile, 'header');
    }
  }

  onDropFooter(event: DragEvent): void {
    event.preventDefault();
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
      this.footerFile = event.dataTransfer.files[0];
      this.generarPreview(this.footerFile, 'footer');
    }
  }

  private generarPreview(file: File, tipo: 'header' | 'footer'): void {
    const reader = new FileReader();
    reader.onload = (e) => {
      if (tipo === 'header') {
        this.headerPreview = e.target?.result as string;
      } else {
        this.footerPreview = e.target?.result as string;
      }
      this.cdr.detectChanges();
    };
    reader.readAsDataURL(file);
  }

  confirmarActualizarLogos(): void {
    if (!this.headerFile && !this.footerFile) return;

    this.subiendoLogos = true;
    this.cdr.detectChanges();

    const formData = new FormData();
    if (this.headerFile) {
      formData.append('header_banner', this.headerFile);
    }
    if (this.footerFile) {
      formData.append('footer_banner', this.footerFile);
    }

    this.ssService.actualizarLogos(formData).subscribe({
      next: (res: any) => {
        this.subiendoLogos = false;
        alert(`✅ ${res.message || 'Logos actualizados correctamente.'}`);
        this.cerrarModalLogos();
      },
      error: (err: any) => {
        this.subiendoLogos = false;
        console.error('Error al actualizar logos:', err);
        alert(err.error?.error || err.error?.message || 'Error al actualizar los logos institucionales.');
        this.cdr.detectChanges();
      }
    });
  }

  // ─────────────────────────────────────────────
  // MÓDULO DE CONVENIOS CON EMPRESAS - MÉTODOS
  // ─────────────────────────────────────────────

  cargarEmpresas(): void {
    this.cargandoEmpresas = true;
    this.errorEmpresas = null;
    const params: any = {};
    if (this.searchQuery) params.search = this.searchQuery;
    if (this.filterEstatus) params.estatus = this.filterEstatus;
    if (this.filterTipoConvenio) params.tipo_convenio = this.filterTipoConvenio;

    this.ssService.getEmpresas(params).subscribe({
      next: (res: any[]) => {
        this.empresasList = res;
        this.cargandoEmpresas = false;
        this.errorEmpresas = null;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('Error al cargar empresas:', err);
        this.cargandoEmpresas = false;
        this.errorEmpresas = err.error?.message || 'No se pudieron cargar las empresas. Es posible que falte la tabla empresas en la base de datos o haya un error de red.';
        this.cdr.detectChanges();
      }
    });
  }

  filtrarEmpresas(): void {
    this.cargarEmpresas();
  }

  abrirModalEmpresa(empresa?: any): void {
    if (empresa) {
      this.editandoEmpresa = true;
      this.empresaIdEnEdicion = empresa.id_empresa;
      // Clonamos para no mutar el listado original
      this.formEmpresa = { ...empresa };
      // Normalizar campos null o fechas
      if (empresa.fecha_firma) {
        this.formEmpresa.fecha_firma = this.formatDate(empresa.fecha_firma);
      }
      if (empresa.fecha_termino) {
        this.formEmpresa.fecha_termino = this.formatDate(empresa.fecha_termino);
      }
    } else {
      this.editandoEmpresa = false;
      this.empresaIdEnEdicion = null;
      this.formEmpresa = {
        anio: new Date().getFullYear(),
        empresa: '',
        tipo_empresa: 'Privada',
        rfc: '',
        direccion: '',
        tipo_convenio: 'Servicio Social',
        fecha_firma: '',
        vigencia: 3,
        fecha_termino: '',
        convenio_fisico: 'No',
        representante: '',
        cargo: '',
        contacto: '',
        telefono: '',
        correo: '',
        igem: false,
        itics: false,
        ilog: false,
        ind: false,
        idam: false,
        ife: false,
        proyectos: '',
        comentarios: ''
      };
    }
    this.mostrarModalEmpresa = true;
    this.cdr.detectChanges();
  }

  cerrarModalEmpresa(): void {
    this.mostrarModalEmpresa = false;
    this.editandoEmpresa = false;
    this.empresaIdEnEdicion = null;
    this.cdr.detectChanges();
  }

  guardarEmpresa(): void {
    if (!this.formEmpresa.empresa || !this.formEmpresa.tipo_empresa || !this.formEmpresa.tipo_convenio) {
      alert('⚠️ Por favor completa los campos obligatorios (*).');
      return;
    }

    // Autocalcular fecha de término si no se especificó y hay firma + vigencia
    if (this.formEmpresa.fecha_firma && this.formEmpresa.vigencia && !this.formEmpresa.fecha_termino) {
      const fecha = new Date(this.formEmpresa.fecha_firma);
      fecha.setFullYear(fecha.getFullYear() + Number(this.formEmpresa.vigencia));
      this.formEmpresa.fecha_termino = this.formatDate(fecha);
    }

    if (this.editandoEmpresa && this.empresaIdEnEdicion) {
      this.ssService.actualizarEmpresa(this.empresaIdEnEdicion, this.formEmpresa).subscribe({
        next: (res: any) => {
          alert('✅ ' + (res.message || 'Empresa actualizada correctamente.'));
          this.cerrarModalEmpresa();
          this.cargarEmpresas();
        },
        error: (err: any) => {
          console.error(err);
          alert('❌ ' + (err.error?.message || 'Error al actualizar los datos de la empresa.'));
        }
      });
    } else {
      this.ssService.crearEmpresa(this.formEmpresa).subscribe({
        next: (res: any) => {
          alert('✅ ' + (res.message || 'Empresa registrada correctamente.'));
          this.cerrarModalEmpresa();
          this.cargarEmpresas();
        },
        error: (err: any) => {
          console.error(err);
          alert('❌ ' + (err.error?.message || 'Error al registrar la empresa.'));
        }
      });
    }
  }

  eliminarEmpresa(empresa: any): void {
    if (confirm(`⚠️ ¿Estás seguro de que deseas eliminar el convenio con la empresa "${empresa.empresa}"?\nEsta acción no se puede deshacer y borrará todo su historial.`)) {
      this.ssService.eliminarEmpresa(empresa.id_empresa).subscribe({
        next: (res: any) => {
          alert('✅ Convenio eliminado.');
          this.cargarEmpresas();
        },
        error: (err: any) => {
          console.error(err);
          alert('❌ Error al eliminar el convenio.');
        }
      });
    }
  }

  abrirModalRenovacion(empresa: any): void {
    this.empresaIdRenovacion = empresa.id_empresa;
    this.empresaNombreRenovacion = empresa.empresa;
    this.formRenovacion = {
      nueva_fecha_firma: this.formatDate(new Date()),
      nueva_vigencia: 3,
      nueva_fecha_termino: '',
      comentarios: ''
    };
    this.calcularNuevaFechaTermino();
    this.mostrarModalRenovacion = true;
    this.cdr.detectChanges();
  }

  cerrarModalRenovacion(): void {
    this.mostrarModalRenovacion = false;
    this.empresaIdRenovacion = null;
    this.empresaNombreRenovacion = '';
    this.cdr.detectChanges();
  }

  calcularNuevaFechaTermino(): void {
    if (this.formRenovacion.nueva_fecha_firma && this.formRenovacion.nueva_vigencia) {
      const fecha = new Date(this.formRenovacion.nueva_fecha_firma);
      fecha.setFullYear(fecha.getFullYear() + Number(this.formRenovacion.nueva_vigencia));
      this.formRenovacion.nueva_fecha_termino = this.formatDate(fecha);
      this.cdr.detectChanges();
    }
  }

  confirmarRenovacion(): void {
    if (!this.empresaIdRenovacion) return;

    this.ssService.renovarConvenio(this.empresaIdRenovacion, this.formRenovacion).subscribe({
      next: (res: any) => {
        alert('✅ ' + (res.message || 'Convenio renovado con éxito.'));
        this.cerrarModalRenovacion();
        this.cargarEmpresas();
      },
      error: (err: any) => {
        console.error(err);
        alert('❌ ' + (err.error?.message || 'Error al procesar la renovación de vigencia.'));
      }
    });
  }

  abrirModalHistorial(empresa: any): void {
    this.empresaNombreHistorial = empresa.empresa;
    this.ssService.getEmpresa(empresa.id_empresa).subscribe({
      next: (res: any) => {
        this.historialRenovaciones = res.renovaciones || [];
        this.mostrarModalHistorial = true;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error(err);
        alert('❌ No se pudo cargar el historial de renovaciones.');
      }
    });
  }

  cerrarModalHistorial(): void {
    this.mostrarModalHistorial = false;
    this.historialRenovaciones = [];
    this.empresaNombreHistorial = '';
    this.cdr.detectChanges();
  }

  private formatDate(date: any): string {
    try {
      const d = new Date(date);
      if (isNaN(d.getTime())) return '';
      let month = '' + (d.getMonth() + 1);
      let day = '' + d.getDate();
      const year = d.getFullYear();

      if (month.length < 2) month = '0' + month;
      if (day.length < 2) day = '0' + day;

      return [year, month, day].join('-');
    } catch (e) {
      return '';
    }
  }
}