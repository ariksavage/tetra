import { CoreService } from '@tetra/services/core.service';
import { AppService } from '@tetra/services/app.service';
import { UserService } from '@tetra/services/user.service';
import { mockCoreService } from '@tetra/services/core.service.mock';
import { mockAppService } from '@tetra/services/app.service.mock';
import { mockUserService } from '@tetra/services/user.service.mock';

export const rootServiceProviders = [
  { provide: CoreService, useValue: mockCoreService },
  { provide: AppService, useValue: mockAppService },
  { provide: UserService, useValue: mockUserService },
];

export function overrideRootServices(testBed: any) {
  return testBed
    .overrideProvider(CoreService, { useValue: mockCoreService })
    .overrideProvider(AppService, { useValue: mockAppService })
    .overrideProvider(UserService, { useValue: mockUserService });
}
