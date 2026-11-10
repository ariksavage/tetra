import { of } from 'rxjs';

export const mockAppService = {
  init: jest.fn(),
  getConfig: jest.fn().mockReturnValue(of({})),
  getPageTitle: jest.fn().mockReturnValue(of('')),
  getBodyClass: jest.fn().mockReturnValue(of('')),
  getPageConfig: jest.fn().mockReturnValue(of(null)),
  setPageTitle: jest.fn(),
  setBodyClass: jest.fn(),
  setPageConfig: jest.fn(),
  message: jest.fn(),
  fetchAppConfig: jest.fn().mockResolvedValue(undefined),
  getSecondaryNav: jest.fn().mockReturnValue(of([{}])),
  getBreadcrumbs: jest.fn().mockReturnValue(of([{}]))
};
