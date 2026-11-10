import { of } from 'rxjs';

export const mockUserService = {
  getUser: jest.fn().mockReturnValue(of(null)),
  logout: jest.fn(),
  login: jest.fn().mockResolvedValue({}),
  loginByToken: jest.fn().mockResolvedValue({}),
  isLoggedIn: jest.fn().mockReturnValue(false),
  forgotPassword: jest.fn().mockResolvedValue({ message: '' }),
};
