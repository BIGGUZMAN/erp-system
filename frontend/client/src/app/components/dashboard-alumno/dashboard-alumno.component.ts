import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';

// Imports de componentes de sección
import { SeccionInglesComponent } from './seccion-ingles/seccion-ingles.component';
import { SeccionServicioComponent } from './seccion-servicio/seccion-servicio.component';
import { SeccionResidenciasComponent } from './seccion-residencias/seccion-residencias.component';

// Import del servicio de inglés
import { InglesService } from '../../services/ingles-data.service';

@Component({
  selector: 'app-dashboard-alumno',
  standalone: true,
  imports: [
    CommonModule,
    SeccionInglesComponent,
    SeccionServicioComponent,
    SeccionResidenciasComponent
  ],
  templateUrl: './dashboard-alumno.component.html',
  styleUrls: ['./dashboard-alumno.component.css']
})
export class DashboardAlumnoComponent implements OnInit {
  seccionActiva: string = 'inicio';
  usuario: any = null;

  // Inicializamos con valores base
  resumenIngles: any = {
    nivel: 1,
    avance: 0
  };
  // Loading indicator for data fetch
  isLoading: boolean = false;

  constructor(
    private router: Router,
    private inglesService: InglesService,
    private cdr: ChangeDetectorRef // Inyectamos el detector de cambios
  ) {
    // 1. Recuperamos al usuario lo más pronto posible
    const userData = localStorage.getItem('usuario');
    if (userData) {
      this.usuario = JSON.parse(userData);
    }
  }

  ngOnInit() {
    if (!this.usuario) {
      this.router.navigate(['/login']);
      return;
    }

    // Show loading spinner while waiting for data
    this.isLoading = true;

    // Reactive listen
    this.inglesService.estado$.subscribe(res => {
      if (res) {
        this.resumenIngles.nivel = res.nivel_siguiente;
        this.resumenIngles.avance = res.porcentaje_total;
        this.cdr.detectChanges();
        // Data loaded, hide spinner
        this.isLoading = false;
      }
    });

    // Trigger initial load
    this.cargarDatosGlobales();
  }

  /**
   * Obtiene el progreso real del alumno. 
   * Al suscribirse aquí, se dispara el 'tap' en el servicio que actualiza el Subject.
   */
  cargarDatosGlobales() {
    const id = this.usuario?.id_usuario || this.usuario?.id;

    if (id) {
      this.inglesService.getMiEstadoActual(id).subscribe();
    }
  }

  /**
   * Método global para abrir archivos (PDFs o HTML renderizado)
   */
  abrirConstancia(url: string) {
    if (url) {
      window.open(url, '_blank');
    }
  }

  cambiarSeccion(seccion: string): void {
    this.seccionActiva = seccion;

    // Al volver al inicio, refrescamos para asegurar sincronía
    if (seccion === 'inicio') {
      this.cargarDatosGlobales();
    }
  }

  cerrarSesion(): void {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    this.router.navigate(['/login']);
  }
}