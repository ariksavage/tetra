import { ConnectionService } from './connection.service';

describe('ConnectionService', () => {
  describe('classify', () => {
    it('says unreachable when nothing answered', () => {
      expect(ConnectionService.classify(0, null)).toBe('unreachable');
    });

    it("says database when the API reports its database is down", () => {
      expect(ConnectionService.classify(503, { code: 503, type: 'Database' })).toBe('database');
    });

    it("says unreachable for a proxy's gateway error with no Tetra body", () => {
      expect(ConnectionService.classify(504, 'Error occurred while trying to proxy')).toBe('unreachable');
      expect(ConnectionService.classify(502, null)).toBe('unreachable');
    });

    it("is fine when the API answered, even with an error of its own", () => {
      expect(ConnectionService.classify(200, {})).toBe('');
      expect(ConnectionService.classify(401, { code: 401 })).toBe('');
      expect(ConnectionService.classify(500, { code: 500, type: 'MySQL' })).toBe('');
    });
  });

  describe('report', () => {
    beforeEach(() => jest.useFakeTimers());
    afterEach(() => jest.useRealTimers());

    it('checks again while there is a problem, and stops once it clears', () => {
      const service = new ConnectionService();
      const check = jest.spyOn(service, 'check').mockResolvedValue('unreachable');
      service.report('unreachable');
      expect(service.problem()).toBe('unreachable');
      jest.advanceTimersByTime(ConnectionService.RETRY_MS);
      expect(check).toHaveBeenCalledTimes(1);
      service.report('');
      expect(service.problem()).toBe('');
      jest.advanceTimersByTime(ConnectionService.RETRY_MS * 2);
      expect(check).toHaveBeenCalledTimes(1);
    });
  });
});
