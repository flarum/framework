/**
 * Haptic feedback utility.
 *
 * Delegates to the `web-haptics` package, which uses the Web Vibration API
 * (`navigator.vibrate`). That API is implemented by Chromium, so in practice haptics are
 * available on Chromium-based Android browsers and nowhere else. iOS Safari has no
 * vibration API, and WebKit's position on the specification is `oppose`. Firefox removed
 * its implementation in Firefox 129.
 *
 * Calls are a no-op wherever the API is absent, so `haptic()` is always safe to call
 * unconditionally. Use {@link isHapticSupported} to decide whether to show haptic-related UI.
 *
 * **User gesture requirement:** haptic calls must occur within a synchronous user gesture
 * context. Always call `haptic()` before any `await` or `.then()` — once execution goes
 * async, the browser's gesture token expires and the haptic is silently ignored.
 *
 * @see https://github.com/lochie/web-haptics
 * @see https://github.com/WebKit/standards-positions/issues/267
 */

import app from '../app';
import { WebHaptics } from 'web-haptics';
import type { HapticInput } from 'web-haptics';

export type { HapticInput };

/**
 * Whether the current browser can produce haptic feedback.
 *
 * Determined by the presence of the Web Vibration API, so this is `true` on Chromium-based
 * Android browsers and `false` on iOS, desktop, and any other browser without it.
 *
 * Use this to conditionally show haptic-related UI (e.g. a settings toggle).
 */
export const isHapticSupported: boolean = typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function';

const _haptics = new WebHaptics();

/**
 * Trigger a haptic feedback pattern on supported mobile devices.
 *
 * @param pattern A {@link HapticPreset} name, a duration in ms, or a custom vibration pattern array.
 *
 * @example <caption>Named presets</caption>
 * haptic('light');        // gentle tap — toggles, selections
 * haptic('medium');       // moderate tap — confirmations
 * haptic('heavy');        // strong tap — destructive actions
 * haptic('success');      // double tap — positive actions (e.g. likes)
 * haptic('warning');      // double pulse — caution
 * haptic('error');        // triple pulse — validation errors
 * haptic('nudge');        // long + short — attention, reminders
 *
 * @example <caption>Custom patterns</caption>
 * haptic(50);             // single vibration, 50ms
 * haptic([100, 50, 100]); // vibrate 100ms, pause 50ms, vibrate 100ms
 */
export default function haptic(pattern: HapticInput = 'light'): void {
  if (!isHapticSupported) return;

  if (app.session?.user && app.session.user.preferences()?.hapticFeedback === false) return;

  _haptics.trigger(pattern as HapticInput);
}
