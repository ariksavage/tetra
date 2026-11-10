import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { CoreService } from './core.service';
import { MessageService } from '@tetra/services/message.service';
import { ErrorService } from '@tetra/services/error.service';
import { mockMessageService } from '@tetra/services/message.service.mock';
import { mockErrorService } from '@tetra/services/error.service.mock';

describe('CoreService', () => {
  let service: CoreService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule],
      providers: [
        CoreService,
        { provide: MessageService, useValue: mockMessageService },
        { provide: ErrorService, useValue: mockErrorService },
      ]
    });
    service = TestBed.inject(CoreService);
    httpMock = TestBed.inject(HttpTestingController);
    jest.clearAllMocks();
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should build correct url', () => {
    expect(service.url('users', 'list')).toBe('/core/users/list');
    expect(service.url('recipes', 'get', 5)).toBe('/core/recipes/get/5');
  });

  it('should make a GET request', async () => {
    const promise = service.get('users', 'list');
    const req = httpMock.expectOne('/core/users/list');
    expect(req.request.method).toBe('GET');
    req.flush({ data: { users: [] } });
    const result = await promise;
    expect(result).toEqual({ users: [] });
  });

  it('should make a POST request', async () => {
    const payload = { name: 'Test' };
    const promise = service.post('users', 'create', payload);
    const req = httpMock.expectOne('/core/users/create');
    expect(req.request.method).toBe('POST');
    req.flush({ data: { id: 1 } });
    const result = await promise;
    expect(result).toEqual({ id: 1 });
  });

  it('should set auth token in headers', () => {
    service.setAuth('my-token');
    const config = service.getConfig();
    expect(config.headers.get('Authorization')).toBe('Bearer my-token');
  });
});
