import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth';
import { Router, ActivatedRoute, RouterModule } from '@angular/router';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-cambiar-password',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './cambiar-password.component.html',
  styleUrls: ['./cambiar-password.component.css']
})
export class CambiarPasswordComponent implements OnInit {
  modo: 'solicitar' | 'restablecer' = 'solicitar';
  correo = '';
  token = '';
  password_nuevo = '';
  confirm_password = '';
  showPassword = false;
  isLoading = false;

  constructor(
    private auth: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      const tokenParam = params['token'];
      const emailParam = params['email'];

      if (tokenParam && emailParam) {
        this.token = tokenParam;
        this.correo = emailParam;
        this.modo = 'restablecer';
      } else {
        this.modo = 'solicitar';
      }
    });
  }

  onSolicitarEnlace() {
    this.correo = this.correo.trim();

    if (!this.correo) {
      Swal.fire({
        icon: 'warning',
        title: 'Campo obligatorio',
        text: 'Por favor, introduce tu correo electrónico.',
        confirmButtonColor: '#1A365D'
      });
      return;
    }

    if (!this.correo.endsWith('@gamadero.tecnm.mx')) {
      Swal.fire({
        icon: 'warning',
        title: 'Correo institucional requerido',
        text: 'Por favor, utiliza tu correo institucional (@gamadero.tecnm.mx).',
        confirmButtonColor: '#1A365D'
      });
      return;
    }

    this.isLoading = true;
    Swal.fire({
      title: 'Enviando correo...',
      text: 'Por favor espera un momento mientras enviamos el enlace de recuperación.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    this.auth.solicitarRecuperacion(this.correo).subscribe({
      next: (res: any) => {
        this.isLoading = false;
        Swal.fire({
          icon: 'success',
          title: '¡Correo enviado!',
          text: res.message || 'Se ha enviado un enlace de recuperación a tu correo.',
          confirmButtonColor: '#1A365D'
        }).then(() => {
          this.router.navigate(['/login']);
        });
      },
      error: (err) => {
        this.isLoading = false;
        const errorMsg = err.error?.message || 'Ocurrió un error al enviar el enlace. Por favor, intenta de nuevo.';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMsg,
          confirmButtonColor: '#1A365D'
        });
      }
    });
  }

  onRestablecerPassword() {
    if (!this.password_nuevo || !this.confirm_password) {
      Swal.fire({
        icon: 'warning',
        title: 'Campos incompletos',
        text: 'Por favor, completa todos los campos de contraseña.',
        confirmButtonColor: '#1A365D'
      });
      return;
    }

    if (this.password_nuevo.length < 6) {
      Swal.fire({
        icon: 'warning',
        title: 'Contraseña débil',
        text: 'La nueva contraseña debe tener al menos 6 caracteres.',
        confirmButtonColor: '#1A365D'
      });
      return;
    }

    if (this.password_nuevo !== this.confirm_password) {
      Swal.fire({
        icon: 'warning',
        title: 'Contraseñas no coinciden',
        text: 'La confirmación de la contraseña no coincide con la nueva contraseña.',
        confirmButtonColor: '#1A365D'
      });
      return;
    }

    this.isLoading = true;
    Swal.fire({
      title: 'Restableciendo contraseña...',
      text: 'Por favor espera un momento.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const datosRestablecer = {
      token: this.token,
      correo: this.correo,
      password: this.password_nuevo,
      password_confirmation: this.confirm_password
    };

    this.auth.restablecerPassword(datosRestablecer).subscribe({
      next: (res: any) => {
        this.isLoading = false;
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: res.message || 'Tu contraseña ha sido restablecida con éxito.',
          confirmButtonColor: '#1A365D'
        }).then(() => {
          this.router.navigate(['/login']);
        });
      },
      error: (err) => {
        this.isLoading = false;
        const errorMsg = err.error?.message || 'El enlace de recuperación no es válido o ha expirado.';
        Swal.fire({
          icon: 'error',
          title: 'Error al restablecer',
          text: errorMsg,
          confirmButtonColor: '#1A365D'
        });
      }
    });
  }

  cancelar() {
    this.router.navigate(['/login']);
  }
}