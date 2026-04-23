import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { provideRouter } from '@angular/router';
import { routes } from './app/app.routes';
import { provideHttpClient, withInterceptors } from '@angular/common/http';

// Si usas interceptores (por ejemplo para tokens), puedes agregarlos aquí
// const authInterceptor = (req: HttpRequest<any>, next: HttpHandler) => {
//   const cloned = req.clone({ setHeaders: { Authorization: `Bearer ${token}` } });
//   return next.handle(cloned);
// };

bootstrapApplication(AppComponent, {
  providers: [
    provideRouter(routes),
    provideHttpClient(
      // Si necesitas interceptores, descomenta la línea de abajo
      // withInterceptors([authInterceptor])
    )
  ]
}).catch(err => console.error(err));
