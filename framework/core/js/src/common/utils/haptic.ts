/**
 * Haptic feedback utility.
 *
 * Uses the `web-haptics` package which supports:
 * - Android: via the Web Vibration API (`navigator.vibrate`)
 * - iOS: via a hidden `<input type="checkbox" switch>` toggle that triggers the Taptic Engine
 *
 * Silently no-ops on desktop and unsupported devices — always safe to call unconditionally.
 *
 * **User gesture requirement (Android + iOS):** Both platforms require haptic calls to occur
 * within a synchronous user gesture context. Always call `haptic()` before any `await` or
 * `.then()` — once execution goes async, the browser's gesture token expires and the haptic
 * will be silently ignored.
 *
 * @see https://github.com/lochie/web-haptics
 */

import app from '../app';
import { WebHaptics } from 'web-haptics';
import type { HapticInput } from 'web-haptics';

export type { HapticInput };

/**
 * Whether the current device supports haptic feedback.
 *
 * `true` on Android (Web Vibration API) and iOS (Taptic Engine via checkbox trick).
 * `false` on desktop browsers.
 *
 * Useful for conditionally showing haptic-related UI (e.g. a settings toggle).
 */
export const isHapticSupported: boolean =
  (typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function') ||
  // iOS supports haptics via the <input type="checkbox" switch> trick
  /iP(hone|ad|od)/.test(typeof navigator !== 'undefined' ? navigator.userAgent : '');

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
  if (app.session?.user && app.session.user.preferences()?.hapticFeedback === false) return;

  _haptics.trigger(pattern as HapticInput);
}
