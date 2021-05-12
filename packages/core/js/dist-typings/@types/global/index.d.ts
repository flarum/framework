// Mithril
import Mithril from 'mithril';

// Other third-party libs
import * as _dayjs from 'dayjs';
import * as _$ from 'jquery';

// Globals from flarum/core
import Application from '../../src/common/Application';

import type { TooltipJQueryFunction } from '../tooltips';

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
  // $ is already defined by `@types/jquery`
  const m: Mithril.Static;
  const dayjs: typeof _dayjs;

  // Extend JQuery with our custom functions, defined with $.fn
  interface JQuery {
    tooltip: TooltipJQueryFunction;
  }
}

/**
 * All global variables owned by flarum/core.
 */
declare global {
  const app: Application;
}
