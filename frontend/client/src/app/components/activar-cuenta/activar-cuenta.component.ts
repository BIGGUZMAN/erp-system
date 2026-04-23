import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth';
import { Router, RouterModule } from '@angular/router';

@Component({
  selector: 'app-activar-cuenta',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterModule],
  templateUrl: './activar-cuenta.component.html',
  styleUrls: ['./activar-cuenta.component.css']
})
export class ActivarCuentaComponent implements OnInit {

  userData = {
    numero_control: '',
    nombre_completo: '',
    correo: '',
    password: '',
    password_confirmation: '',
    carrera_id: null
  };

  mostrarCarreras = false;
  carreras: any[] = [];

  constructor(private auth: AuthService, private router: Router) { }

  ngOnInit() {
    this.auth.obtenerCarreras().subscribe({
      next: (res: any) => {
        this.carreras = res.carreras;
      },
      error: (err) => console.error('Error al cargar las carreras:', err)
    });
  }

  // ESTA ES LA FUNCIÓN QUE CORREGIMOS
  verificarTipoUsuario() {
    // Normalizamos a mayúsculas para que detecte la 'C' o 'B'
    this.userData.numero_control = this.userData.numero_control.toUpperCase();
    const numControl = this.userData.numero_control;

    /**
     * NUEVA LÓGICA:
     * El menú de carreras se mostrará si el número de control:
     * 1. Son solo 8 o 9 números (ej. 221130028)
     * 2. O empieza con una letra y sigue con 8 o 9 números (ej. C221130028)
     */
    const esFormatoAlumno = /^[A-Z]?\d{8,9}$/.test(numControl);

    if (esFormatoAlumno) {
      this.mostrarCarreras = true;
    } else {
      // Si es un admin (como '002' o prefijo 'CLE'), ocultamos las carreras
      this.mostrarCarreras = false;
      this.userData.carrera_id = null;
    }
  }

  onSubmit() {
    // Validaciones de seguridad
    if (this.userData.password !== this.userData.password_confirmation) {
      alert('Las contraseñas no coinciden');
      return;
    }

    if (this.mostrarCarreras && !this.userData.carrera_id) {
      alert('Por favor, selecciona tu carrera antes de continuar.');
      return;
    }

    this.auth.activarCuenta(this.userData).subscribe({
      next: (res: any) => {
        alert(res.message || 'Cuenta activada correctamente');
        this.router.navigate(['/login']);
      },
      error: (err) => {
        alert(err.error.message || 'Error al activar la cuenta');
      }
    });
  }
}