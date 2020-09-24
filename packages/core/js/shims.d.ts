// Mithril
import * as Mithril from 'mithril';
import Stream from 'mithril/stream';

// Other third-party libs
import * as _dayjs from 'dayjs';
import * as _$ from 'jquery';

// Globals from flarum/core
import Application from './src/common/Application';

/**
 * Helpers that flarum/core patches into Mithril
 */
interface m extends Mithril.Static {
  prop: typeof Stream;
}

/**
 * Export Mithril typings globally.
 *
 * This lets us use these typings without an extra import everywhere we use
 * Mithril in a TypeScript file.
 */
export as namespace Mithril;

/**
 * flarum/core exposes several extensions globally:
 *
 * - jQuery for convenient DOM manipulation
 * - Mithril for VDOM and components
 * - dayjs for date/time operations
 *
 * Since these are already part of the global namespace, extensions won't need
 * to (and should not) bundle these themselves.
 */
declare global {
  const $: typeof _$;
  const m: m;
  const dayjs: typeof _dayjs;
}

/**
 * All global variables owned by flarum/core.
 */
declare global {
  const app: Application;
}
