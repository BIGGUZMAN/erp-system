import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { InglesService } from '../../services/ingles-data.service';
import Swal from 'sweetalert2';

@Component({
    selector: 'app-inscribir-alumno',
    standalone: true,
    imports: [CommonModule, ReactiveFormsModule, FormsModule],
    templateUrl: './inscribir-alumno.component.html',
    styleUrls: ['./inscribir-alumno.component.css']
})
export class InscripcionInglesComponent implements OnInit {

    inscripcionForm!: FormGroup;
    archivoSeleccionado: File | null = null;
    carreras: any[] = [];
    niveles: any[] = [];

    // Lista de grupos para el selector
    gruposDisponibles: string[] = ['A', 'B', 'C', 'D'];

    constructor(
        private fb: FormBuilder,
        private inglesService: InglesService,
        public router: Router
    ) { }

    ngOnInit(): void {
        this.initForm();
        this.cargarCatalogos();
    }

    initForm(): void {
        this.inscripcionForm = this.fb.group({
            numero_control: ['', [Validators.required, Validators.minLength(8)]],
            nombre_completo: ['', Validators.required],
            carrera_id: ['', Validators.required],
            nivel_id: ['', Validators.required],
            ciclo_escolar: ['', Validators.required],
            modalidad: ['', Validators.required],
            grupo: ['A', Validators.required], // Nuevo campo agregado con valor inicial 'A'
            fecha_inicio: [''],
            fecha_fin: [''],
            comprobante: [null],
            pago_pendiente: [false]
        });
    }

    cargarCatalogos(): void {
        this.inglesService.getCarreras().subscribe({
            next: (res) => this.carreras = res,
            error: (err) => console.error('Error cargando carreras', err)
        });

        this.inglesService.getNiveles().subscribe({
            next: (res) => this.niveles = res,
            error: (err) => console.error('Error cargando niveles', err)
        });
    }

    buscarDatosAlumno(): void {
        const numControl = this.inscripcionForm.get('numero_control')?.value;
        if (numControl && numControl.length >= 8) {
            this.inglesService.buscarAlumnoPorControl(numControl).subscribe({
                next: (res: any) => {
                    if (res && res.encontrado) {
                        this.inscripcionForm.patchValue({
                            nombre_completo: res.nombre_completo,
                            carrera_id: res.carrera_id
                        });
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        Toast.fire({ icon: 'success', title: 'Alumno encontrado' });
                    }
                }
            });
        }
    }

    onFileSelected(event: any): void {
        const file: File = event.target.files[0];
        if (file) {
            if (file.size <= 5 * 1024 * 1024) {
                this.archivoSeleccionado = file;
                this.inscripcionForm.patchValue({ comprobante: file.name });
            } else {
                Swal.fire('Error', 'El archivo excede los 5MB', 'error');
                event.target.value = '';
                this.archivoSeleccionado = null;
            }
        }
    }

    guardarInscripcion(): void {
        if (this.inscripcionForm.invalid) {
            Swal.fire('Atención', 'Completa los campos obligatorios.', 'warning');
            return;
        }

        const isPendiente = this.inscripcionForm.get('pago_pendiente')?.value;
        if (!this.archivoSeleccionado && !isPendiente) {
            Swal.fire('Atención', 'Debes adjuntar el comprobante o marcarlo como pendiente.', 'warning');
            return;
        }

        const formData = new FormData();

        // Iterar sobre todos los campos y agregarlos al FormData
        Object.keys(this.inscripcionForm.value).forEach(key => {
            if (key !== 'comprobante' && key !== 'pago_pendiente') {
                formData.append(key, this.inscripcionForm.get(key)?.value);
            }
        });

        // Aseguramos el envío del estado de pago y el grupo
        formData.append('pago_pendiente', isPendiente ? '1' : '0');

        if (this.archivoSeleccionado) {
            formData.append('comprobante', this.archivoSeleccionado);
        }

        this.inglesService.inscribirAlumno(formData).subscribe({
            next: () => {
                Swal.fire('¡Éxito!', 'Alumno inscrito correctamente en el Grupo ' + this.inscripcionForm.get('grupo')?.value, 'success');
                this.router.navigate(['/dashboard-ingles']);
            },
            error: (err) => {
                const errorMsg = err.error?.message || 'No se pudo guardar la inscripción';
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    }
}