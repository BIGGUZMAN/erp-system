import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { Router } from '@angular/router';
import { InglesService } from '../../services/ingles-data.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';

interface Nivel {
  id: number;
  nombre: string;
  clasificacion: string;
  numero: number;
  cupo_maximo: number;
  inscripciones_count: number;
}

@Component({
  selector: 'app-dashboard-ingles',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard-ingles.component.html',
  styleUrls: ['./dashboard-ingles.component.css']
})
export class DashboardInglesComponent implements OnInit {

  nivelesBasicos: Nivel[] = [];
  nivelesIntermedios: Nivel[] = [];

  totalEnrollment: number = 0;
  gruposActivos: number = 0;
  tasaAprobacion: number = 0;

  cargando: boolean = true;

  // NUEVAS PROPIEDADES PARA EL BUSCADOR DE ALUMNOS
  searchNC: string = '';
  alumnoHistorial: any = null;
  buscando: boolean = false;
  realizoBusqueda: boolean = false;

  constructor(
    private inglesService: InglesService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit(): void {
    this.cargarDashboard();
  }

  cargarDashboard(): void {
    this.cargando = true;

    this.inglesService.getDashboardData().subscribe({
      next: (res: any) => {
        console.log('Respuesta backend:', res);

        // KPIs
        this.totalEnrollment = res?.totalEnrollment ?? 0;
        this.gruposActivos = res?.gruposActivos ?? 0;
        this.tasaAprobacion = res?.tasaAprobacion ?? 0;

        // Datos de niveles
        if (Array.isArray(res?.niveles)) {

          const niveles: Nivel[] = res.niveles;

          this.nivelesBasicos = niveles.filter(
            n => n.clasificacion === 'Basico'
          );

          this.nivelesIntermedios = niveles.filter(
            n => n.clasificacion === 'Intermedio'
          );

        } else {
          console.warn('La respuesta no contiene niveles válidos');
          this.nivelesBasicos = [];
          this.nivelesIntermedios = [];
        }

        console.log('Basicos:', this.nivelesBasicos.length);
        console.log('Intermedios:', this.nivelesIntermedios.length);

        this.cargando = false;

        // 🔥 Forzar actualización de la vista
        this.cdr.detectChanges();
      },

      error: (err) => {
        console.error('Error conectando con Laravel:', err);

        this.nivelesBasicos = [];
        this.nivelesIntermedios = [];

        this.cargando = false;

        this.cdr.detectChanges();
      }
    });
  }

  verDetalles(id: number): void {
    this.router.navigate(['/curso-detalle', id]);
  }

  irAInscripcion(): void {
    this.router.navigate(['/inscribir-alumno']);
  }

  buscarAlumno(): void {
    if (!this.searchNC.trim()) {
      Swal.fire('Atención', 'Por favor, ingrese un número de control válido.', 'warning');
      return;
    }

    this.buscando = true;
    this.realizoBusqueda = true;
    this.alumnoHistorial = null;

    this.inglesService.buscarHistorial(this.searchNC.trim()).subscribe({
      next: (res: any) => {
        this.buscando = false;
        if (res && res.encontrado) {
          this.alumnoHistorial = res;
        } else {
          this.alumnoHistorial = null;
          Swal.fire('No encontrado', res?.message || 'No se encontró ningún alumno con ese número de control.', 'info');
        }
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.buscando = false;
        console.error('Error al buscar historial:', err);
        Swal.fire('Error', 'Ocurrió un error al buscar al alumno.', 'error');
        this.cdr.detectChanges();
      }
    });
  }

  limpiarBuscador(): void {
    this.searchNC = '';
    this.alumnoHistorial = null;
    this.realizoBusqueda = false;
    this.cdr.detectChanges();
  }

  cerrarSesion(): void {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    this.router.navigate(['/login']);
  }
}