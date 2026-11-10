import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { AppService } from './app.service';
import { CoreService } from '@tetra/services/core.service';
import { mockCoreService } from '@tetra/services/core.service.mock';

describe('AppService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        AppService,
        { provide: CoreService, useValue: mockCoreService },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: { params: {}, queryParams: {} },
            params: of({}),
            queryParams: of({})
          }
        }
      ]
    });
  });

  it('should be created', () => {
    const service = TestBed.inject(AppService);
    expect(service).toBeTruthy();
  });
});
