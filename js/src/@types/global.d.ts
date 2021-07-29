declare const m: import('mithril').Static;
declare const app: import('../common/Application').default;
declare const dayjs: typeof import('dayjs');

// Extend JQuery with our custom functions, defined with $.fn
interface JQuery {
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
