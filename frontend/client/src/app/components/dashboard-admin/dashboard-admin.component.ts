import { Component } from '@angular/core';
import { CommonModule } from '@angular/common'; // <-- 1. Importación recomendada

@Component({
  selector: 'app-dashboard-admin',
  standalone: true, // <-- 2. Asegúrate de que esté marcado como standalone
  imports: [CommonModule], // <-- 3. Agregamos CommonModule
  templateUrl: './dashboard-admin.component.html', // <-- 4. CORREGIDO: faltaba ".component"
  styleUrl: './dashboard-admin.component.css', // <-- 5. CORREGIDO: faltaba ".component"
})
export class DashboardAdminComponent { }