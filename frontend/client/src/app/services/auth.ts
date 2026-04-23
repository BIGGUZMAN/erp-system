import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) { }

  activarCuenta(datos: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/activar-cuenta`, datos);
  }

  login(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, data);
  }

  cambiarPassword(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/cambiar-password`, data);
  }

  // Nuevo método para obtener la lista de carreras
  obtenerCarreras(): Observable<any> {
    return this.http.get(`${this.apiUrl}/carreras`);
  }

  recuperarPassword(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/recuperar-password`, data);
  }
}