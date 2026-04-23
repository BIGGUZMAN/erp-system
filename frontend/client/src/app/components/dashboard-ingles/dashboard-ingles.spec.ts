import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardInglesComponent } from './dashboard-ingles.component';

describe('DashboardInglesComponent', () => {
  let component: DashboardInglesComponent;
  let fixture: ComponentFixture<DashboardInglesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardInglesComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardInglesComponent);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
