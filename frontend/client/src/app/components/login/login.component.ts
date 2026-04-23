import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth';
import { Router, RouterModule } from '@angular/router';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent {
  loginData = {
    numero_control: '',
    password: ''
  };

  showPassword = false;

  constructor(private auth: AuthService, public router: Router) { }

  onLogin() {
    if (this.loginData.numero_control) {
      this.loginData.numero_control = this.loginData.numero_control.toUpperCase().trim();
    }

    if (!this.loginData.numero_control || !this.loginData.password) {
      alert('Por favor, completa todos los campos.');
      return;
    }

    this.auth.login(this.loginData).subscribe({
      next: (res: any) => {
        // --- CAMBIOS AQUÍ ---
        // 1. Guardamos el objeto completo del usuario (importante para el Dashboard)
        localStorage.setItem('usuario', JSON.stringify(res.usuario));

        // 2. Guardamos el tipo para tus validaciones actuales
        localStorage.setItem('tipo_usuario', res.tipo);

        // 3. Si el backend envía un token, guárdalo también
        if (res.token) {
          localStorage.setItem('token', res.token);
        }
        // ---------------------

        if (res.tipo === 'admin_ingles') {
          this.router.navigate(['/dashboard-ingles']);
        } else if (res.tipo === 'admin') {
          this.router.navigate(['/dashboard-admin']);
        } else {
          // Redirige al dashboard del alumno (Ramirez Garcia Maria Fernanda)
          this.router.navigate(['/dashboard-alumno']);
        }
      },
      error: (err) => {
        const mensaje = err.error?.message || 'Error en las credenciales.';
        alert(mensaje);
      }
    });
  }
}