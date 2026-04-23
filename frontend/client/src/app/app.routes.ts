import { Routes } from '@angular/router';
import { ActivarCuentaComponent } from './components/activar-cuenta/activar-cuenta.component';
import { LoginComponent } from './components/login/login.component';
import { CambiarPasswordComponent } from './components/cambiar-password/cambiar-password.component';
import { DashboardAlumnoComponent } from './components/dashboard-alumno/dashboard-alumno.component';
import { DashboardInglesComponent } from './components/dashboard-ingles/dashboard-ingles.component';
import { DashboardAdminComponent } from './components/dashboard-admin/dashboard-admin.component';
import { InscripcionInglesComponent } from './components/inscribir-alumno/inscribir-alumno.component';
import { CursoDetalleComponent } from './components/curso-detalle/curso-detalle.component';

export const routes: Routes = [
    { path: '', redirectTo: '/activar-cuenta', pathMatch: 'full' },
    { path: 'activar-cuenta', component: ActivarCuentaComponent },
    { path: 'login', component: LoginComponent },
    { path: 'cambiar-password', component: CambiarPasswordComponent },
    {
        path: 'dashboard-alumno',
        component: DashboardAlumnoComponent
        // Se eliminó el resolve para evitar el bloqueo de carga
    },
    { path: 'dashboard-ingles', component: DashboardInglesComponent },
    { path: 'dashboard-admin', component: DashboardAdminComponent },
    { path: 'inscribir-alumno', component: InscripcionInglesComponent },
    { path: 'curso-detalle/:id', component: CursoDetalleComponent },
];