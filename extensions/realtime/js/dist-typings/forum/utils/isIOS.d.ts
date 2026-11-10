/**
 * Detect whether the current browser is running on iOS / iPadOS.
 *
 * All iOS browsers (Safari, Chrome, Firefox, etc.) use WebKit under the hood
 * and inherit Safari's WebSocket-backgrounding pathology — so this needs to
 * be broader than `isSafariMobile()` (which excludes Chrome/Firefox on iOS).
 *
 * Detects iPadOS 13+ via the MacIntel + maxTouchPoints quirk: iPadOS 13+
 * reports `navigator.platform === 'MacIntel'` but `maxTouchPoints > 1`,
 * which desktop macOS never does.
 */
export default function isIOS(): boolean;
