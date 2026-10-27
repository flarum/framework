import { jest } from '@jest/globals';

const define = (prop: string, value: unknown) => Object.defineProperty(navigator, prop, { value, writable: true, configurable: true });

const originalUserAgent = navigator.userAgent;

/**
 * `isHapticSupported` is resolved when the module is first evaluated, so each case has to
 * set the environment up and then load the module fresh.
 */
async function loadHaptic(vibrate: unknown, userAgent: string = originalUserAgent) {
  jest.resetModules();
  define('vibrate', vibrate);
  define('userAgent', userAgent);

  const haptic = await import('../../../../src/common/utils/haptic');
  const { WebHaptics } = await import('web-haptics');

  return { ...haptic, WebHaptics };
}

afterEach(() => {
  define('vibrate', undefined);
  define('userAgent', originalUserAgent);
  jest.restoreAllMocks();
});

describe('haptic', () => {
  describe('on a browser with the Vibration API', () => {
    async function withSpy() {
      const { default: haptic, WebHaptics } = await loadHaptic(jest.fn());
      const trigger = jest.spyOn(WebHaptics.prototype, 'trigger').mockImplementation(() => Promise.resolve());

      return { haptic, trigger };
    }

    it('triggers the light preset by default', async () => {
      const { haptic, trigger } = await withSpy();

      haptic();

      expect(trigger).toHaveBeenCalledWith('light');
    });

    it.each(['light', 'medium', 'heavy', 'success', 'warning', 'error', 'nudge'] as const)('passes preset "%s" through', async (preset) => {
      const { haptic, trigger } = await withSpy();

      haptic(preset);

      expect(trigger).toHaveBeenCalledWith(preset);
    });

    it('accepts a duration in ms', async () => {
      const { haptic, trigger } = await withSpy();

      haptic(50);

      expect(trigger).toHaveBeenCalledWith(50);
    });

    it('accepts a custom pattern array', async () => {
      const { haptic, trigger } = await withSpy();

      haptic([100, 50, 100]);

      expect(trigger).toHaveBeenCalledWith([100, 50, 100]);
    });
  });

  describe('on a browser without the Vibration API', () => {
    /**
     * Reaching the library at all is what matters here, not what it would have done.
     * Its fallback path builds a hidden switch element and drives it from a
     * requestAnimationFrame loop, which is wasted work on a device that cannot vibrate.
     */
    it('does not reach the underlying library', async () => {
      const { default: haptic, WebHaptics } = await loadHaptic(undefined);
      const trigger = jest.spyOn(WebHaptics.prototype, 'trigger').mockImplementation(() => Promise.resolve());

      haptic('success');

      expect(trigger).not.toHaveBeenCalled();
    });

    it('does not throw', async () => {
      const { default: haptic } = await loadHaptic(undefined);

      expect(() => haptic('success')).not.toThrow();
    });

    it('does not reach the library on iOS either', async () => {
      const iOS = 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5 like Mac OS X) AppleWebKit/605.1.15';
      const { default: haptic, WebHaptics } = await loadHaptic(undefined, iOS);
      const trigger = jest.spyOn(WebHaptics.prototype, 'trigger').mockImplementation(() => Promise.resolve());

      haptic('success');

      expect(trigger).not.toHaveBeenCalled();
    });
  });
});

describe('isHapticSupported', () => {
  it('is true when the Vibration API is present', async () => {
    const { isHapticSupported } = await loadHaptic(jest.fn());

    expect(isHapticSupported).toBe(true);
  });

  it('is false when the Vibration API is absent', async () => {
    const { isHapticSupported } = await loadHaptic(undefined);

    expect(isHapticSupported).toBe(false);
  });

  /**
   * iOS has no Vibration API. It briefly produced haptics through an
   * `<input type="checkbox" switch>` quirk, which WebKit closed in iOS 26.5 by requiring
   * a trusted event, so support must not be inferred from the user agent.
   */
  it('is false on iOS, which has no Vibration API', async () => {
    const iOS = 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5 like Mac OS X) AppleWebKit/605.1.15';
    const { isHapticSupported } = await loadHaptic(undefined, iOS);

    expect(isHapticSupported).toBe(false);
  });
});
