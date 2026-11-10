export const mockMessageService = {
  add: jest.fn(),
  error: jest.fn(),
  clear: jest.fn(),
  getMessages: jest.fn().mockReturnValue([]),
};