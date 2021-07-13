// Mithril
import Mithril from 'mithril';

// Other third-party libs
import * as _dayjs from 'dayjs';
import 'dayjs/plugin/relativeTime';
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

  /**
   * For more info, see: https://www.typescriptlang.org/docs/handbook/jsx.html#attribute-type-checking
   *
   * In a nutshell, we need to add `ElementAttributesProperty` to tell Typescript
   * what property on component classes to look at for attribute typings. For our
   * Component class, this would be `attrs` (e.g. `this.attrs...`)
   */
  namespace JSX {
    interface ElementAttributesProperty {
      attrs: Record<string, unknown>;
    }
  }
}

/**
 * All global variables owned by flarum/core.
 */
declare global {
  const app: Application;
}
