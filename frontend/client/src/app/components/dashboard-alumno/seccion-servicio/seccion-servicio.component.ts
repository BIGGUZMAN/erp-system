import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-seccion-servicio',
  standalone: true, // Esto corrige el error NG2012
  imports: [CommonModule],
  templateUrl: './seccion-servicio.component.html', // Corregido según tu captura de archivos
  styleUrl: './seccion-servicio.component.css' // Corregido según tu captura de archivos
})
export class SeccionServicioComponent { }