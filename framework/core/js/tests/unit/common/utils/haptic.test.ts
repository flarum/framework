jest.mock('web-haptics');

import { WebHaptics } from 'web-haptics';
import haptic, { isHapticSupported } from '../../../../src/common/utils/haptic';

// haptic.ts creates `_haptics = new WebHaptics()` at module load.
// Auto-mock replaces prototype methods with jest.fn().
const mockTrigger = WebHaptics.prototype.trigger as jest.Mock;

describe('haptic', () => {
  beforeEach(() => {
    mockTrigger.mockClear();
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

    it('is true when navigator.vibrate is a function', () => {
      Object.defineProperty(navigator, 'vibrate', { value: jest.fn(), writable: true, configurable: true });

      let supported: boolean | undefined;
      jest.isolateModules(() => {
        jest.mock('web-haptics');
        ({ isHapticSupported: supported } = require('../../../../src/common/utils/haptic'));
      });

      expect(supported).toBe(true);

      Object.defineProperty(navigator, 'vibrate', { value: undefined, writable: true, configurable: true });
    });

    it('is true on iOS userAgent', () => {
      const originalUserAgent = navigator.userAgent;
      Object.defineProperty(navigator, 'userAgent', { value: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)', writable: true, configurable: true });

      let supported: boolean | undefined;
      jest.isolateModules(() => {
        jest.mock('web-haptics');
        ({ isHapticSupported: supported } = require('../../../../src/common/utils/haptic'));
      });

      expect(supported).toBe(true);

      Object.defineProperty(navigator, 'userAgent', { value: originalUserAgent, writable: true, configurable: true });
    });
  });
});
