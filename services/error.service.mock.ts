export const mockErrorService = {
  setError: jest.fn(),
  getError: jest.fn().mockReturnValue(null),
  clearError: jest.fn(),
};