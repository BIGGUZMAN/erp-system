import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { InglesService } from '../../../services/ingles-data.service';

@Component({
  selector: 'app-seccion-ingles',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './seccion-ingles.component.html',
  styleUrl: './seccion-ingles.component.css'
})
export class SeccionInglesComponent implements OnInit {
  usuario: any;
  historialIngles: any[] = [];
  progresoGlobal: number = 0;
  nivelesAprobados: number = 0;
  nivelActual: number = 1;
  cargando: boolean = true;

  constructor(
    private inglesService: InglesService,
    private cdr: ChangeDetectorRef // Fundamental para refrescar la UI al recibir datos asíncronos
  ) { }

  ngOnInit(): void {
    // 1. Obtenemos el usuario del storage
    const userJson = localStorage.getItem('usuario');
    if (userJson) {
      this.usuario = JSON.parse(userJson);
    }

    /**
     * 2. CARGA INICIAL SÍNCRONA:
     * Si el Dashboard ya cargó los datos, los tomamos de inmediato para evitar el "0%".
     */
    const estadoActual = this.inglesService.obtenerEstadoActual();
    if (estadoActual) {
      this.mapearDatos(estadoActual);
    }

    /**
     * 3. ESCUCHA REACTIVA AL SUBJECT:
     * Mantenemos la suscripción para que, si los datos cambian mientras el usuario
     * está navegando, la interfaz se actualice sola.
     */
    this.inglesService.estado$.subscribe({
      next: (res) => {
        if (res) {
          console.log('Sección Inglés recibió actualización reactiva:', res);
          this.mapearDatos(res);
        }
      },
      error: (err) => {
        console.error('Error al recibir estado en sección inglés:', err);
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });

    /**
     * 4. CARGA DE SEGURIDAD:
     * Solo si no hay datos en el servicio, disparamos la petición.
     */
    if (!estadoActual) {
      const id = this.usuario?.id_usuario || this.usuario?.id;
      if (id) {
        this.inglesService.getMiEstadoActual(id).subscribe();
      }
    }
  }

  /**
   * Método privado para evitar repetir código de asignación
   */
  private mapearDatos(res: any): void {
    this.historialIngles = res.historial || [];
    this.progresoGlobal = res.porcentaje_total || 0;
    this.nivelesAprobados = res.conteo_aprobados || 0;
    this.nivelActual = res.nivel_siguiente || 1;
    this.cargando = false;

    // VITAL: Notificar a Angular que los datos cambiaron para renderizar el HTML
    this.cdr.detectChanges();
  }

  /**
   * Método para descargar el PDF de la boleta
   */
  descargarBoleta(idInscripcion: number) {
    this.inglesService.descargarBoletaIndividual(idInscripcion).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Boleta_Ingles_${this.usuario?.nombre || 'Alumno'}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        alert('No se pudo generar la boleta. Asegúrate de que el curso esté aprobado.');
        console.error('Error al descargar boleta:', err);
      }
    });
  }
}