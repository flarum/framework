/**
 * @deprecated Please import `app` from a namespace instead of using it as a global variable.
 *
 * @example App in forum JS
 * ```
 * import app from 'flarum/forum/app';
 * ```
 *
 * @example App in admin JS
 * ```
 * import app from 'flarum/admin/app';
 * ```
 *
 * @example App in common JS
 * ```
 * import app from 'flarum/common/app';
 * ```
 */
declare const app: never;

declare const m: import('mithril').Static;
declare const dayjs: typeof import('dayjs');

type ESModule = { __esModule: true; [key: string]: unknown };

/**
 * The global `flarum` variable.
 *
 * Contains the compiled ES Modules for all Flarum extensions and core.
 *
 * @example <caption>Check if `flarum-tags` is present</captions>
 * if ('flarum-tags' in flarum.extensions) {
 *   // Tags is installed and enabled!
 * }
 */
interface FlarumObject {
  /**
   * Contains the compiled ES Module for Flarum's core.
   *
   * You shouldn't need to access this directly for any reason.
   */
  core: Readonly<ESModule>;
  /**
   * Contains the compiled ES Modules for all Flarum extensions.
   *
   * @example <caption>Check if `flarum-tags` is present</captions>
   * if ('flarum-tags' in flarum.extensions) {
   *   // Tags is installed and enabled!
   * }
   */
  extensions: Readonly<Record<string, ESModule>>;
}

declare const flarum: FlarumObject;

// Extend JQuery with our custom functions, defined with $.fn
interface JQuery {
  /**
   * Flarum's tooltip JQuery plugin.
   *
   * Do not use this directly. Instead use the `<Tooltip>` component that
   * is exported from `flarum/common/components/Tooltip`.
   *
   * This may be removed in a future version of Flarum.
   */
  tooltip: import('./tooltips/index').TooltipJQueryFunction;
}

/**
 * For more info, see: https://www.typescriptlang.org/docs/handbook/jsx.html#attribute-type-checking
 *
 * In a nutshell, we need to add `ElementAttributesProperty` to tell Typescript
 * what property on component classes to look at for attribute typings. For our
 * Component class, this would be `attrs` (e.g. `this.attrs...`)
 */
interface JSX {
  ElementAttributesProperty: {
    attrs: Record<string, unknown>;
  };
}
