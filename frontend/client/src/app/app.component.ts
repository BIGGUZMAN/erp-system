import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
    selector: 'app-root',
    standalone: true,
    imports: [RouterOutlet], // Esto permite que las rutas funcionen
    template: `<router-outlet></router-outlet>` // Aquí se "dibujan" tus componentes
})
export class AppComponent { }