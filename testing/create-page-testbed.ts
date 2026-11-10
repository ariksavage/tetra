import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { overrideRootServices, rootServiceProviders } from './override-providers';

export async function createPageTestBed(PageComponent: any) {
  await overrideRootServices(
    TestBed.configureTestingModule({
      imports: [PageComponent, RouterTestingModule],
      providers: [...rootServiceProviders],
    })
  ).compileComponents();

  jest.clearAllMocks();
}
