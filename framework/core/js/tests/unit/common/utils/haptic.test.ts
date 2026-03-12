import { jest } from '@jest/globals';
import { WebHaptics } from 'web-haptics';
import haptic, { isHapticSupported } from '../../../../src/common/utils/haptic';

// haptic.ts creates `_haptics = new WebHaptics()` at module load.
// Spying on the prototype intercepts calls on the already-created instance.
describe('haptic', () => {
  let mockTrigger: ReturnType<typeof jest.spyOn>;

  beforeEach(() => {
    mockTrigger = jest.spyOn(WebHaptics.prototype, 'trigger').mockImplementation(() => Promise.resolve());
  });

  afterEach(() => {
    mockTrigger.mockRestore();
  });

  describe('presets', () => {
    it('triggers the light preset by default', () => {
      haptic();
      expect(mockTrigger).toHaveBeenCalledWith('light');
    });

    it.each(['light', 'medium', 'heavy', 'success', 'warning', 'error', 'nudge'] as const)(
      'passes preset "%s" directly to web-haptics',
      (preset) => {
        haptic(preset);
        expect(mockTrigger).toHaveBeenCalledWith(preset);
      }
    );
  });

  describe('custom patterns', () => {
    it('accepts a duration in ms', () => {
      haptic(50);
      expect(mockTrigger).toHaveBeenCalledWith(50);
    });

    it('accepts a custom pattern array', () => {
      haptic([100, 50, 100]);
      expect(mockTrigger).toHaveBeenCalledWith([100, 50, 100]);
    });
  });

  describe('unsupported devices', () => {
    it('does not throw on any device', () => {
      expect(() => haptic('success')).not.toThrow();
    });
  });

  describe('isHapticSupported', () => {
    it('is false in jsdom (no navigator.vibrate, non-iOS userAgent)', () => {
      expect(isHapticSupported).toBe(false);
    });

    it('is true when navigator.vibrate is a function', async () => {
      Object.defineProperty(navigator, 'vibrate', { value: jest.fn(), writable: true, configurable: true });

      jest.resetModules();
      const { isHapticSupported: supported } = await import('../../../../src/common/utils/haptic');

      expect(supported).toBe(true);

      Object.defineProperty(navigator, 'vibrate', { value: undefined, writable: true, configurable: true });
    });

    it('is true on iOS userAgent', async () => {
      const originalUserAgent = navigator.userAgent;
      Object.defineProperty(navigator, 'userAgent', { value: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)', writable: true, configurable: true });

      jest.resetModules();
      const { isHapticSupported: supported } = await import('../../../../src/common/utils/haptic');

      expect(supported).toBe(true);

      Object.defineProperty(navigator, 'userAgent', { value: originalUserAgent, writable: true, configurable: true });
    });
  });
});
