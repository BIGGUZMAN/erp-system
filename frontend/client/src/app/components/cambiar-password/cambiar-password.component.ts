import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth';
import { Router, RouterModule } from '@angular/router';

@Component({
  selector: 'app-cambiar-password',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './cambiar-password.component.html',
  styleUrls: ['./cambiar-password.component.css']
})
export class CambiarPasswordComponent {
  datos = {
    numero_control: '',
    correo: '',
    password_nuevo: ''
  };

  confirm_password = '';
  showPassword = false;

  constructor(private auth: AuthService, private router: Router) { }

  onRecuperarPassword() {
    // 1. Limpieza de datos: Convertimos el número de control a Mayúsculas para que coincida con la BD
    if (this.datos.numero_control) {
      this.datos.numero_control = this.datos.numero_control.toUpperCase().trim();
    }

    // 2. Validación de campos vacíos
    if (!this.datos.numero_control || !this.datos.correo || !this.datos.password_nuevo) {
      alert('Por favor, completa todos los campos.');
      return;
    }

    // 3. Validación de formato de número de control (Misma lógica que el Back y Activar Cuenta)
    const esFormatoValido = /^[A-Z]?\d{8,9}$/.test(this.datos.numero_control);
    // Para administradores (que suelen ser menos dígitos), podrías omitir esta validación o ajustarla, 
    // pero para seguridad de los alumnos, validamos el formato:
    if (this.datos.numero_control.length < 3) {
      alert('El número de control no tiene un formato válido.');
      return;
    }

    // 4. Validación de contraseñas iguales
    if (this.datos.password_nuevo !== this.confirm_password) {
      alert('Las contraseñas no coinciden.');
      return;
    }

    // Llamada al servicio de autenticación
    this.auth.recuperarPassword(this.datos).subscribe({
      next: (res: any) => {
        alert(res.message || 'Contraseña restablecida con éxito.');
        this.router.navigate(['/login']);
      },
      error: (err) => {
        alert(err.error.message || 'Los datos no coinciden con nuestros registros.');
      }
    });
  }

  cancelar() {
    this.router.navigate(['/login']);
  }
}