/**
 * Whether the forum is open as an app, in a window of its own rather than a
 * browser tab: from a phone's home screen, or installed on a computer.
 *
 * iPhones and iPads say so with `navigator.standalone`; other browsers match
 * the manifest's `standalone` display mode.
 */
export default function isOpenedAsApp(): boolean;
