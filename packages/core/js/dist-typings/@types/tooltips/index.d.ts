/**
 * Selection of options accepted by [Bootstrap's tooltips](https://getbootstrap.com/docs/3.3/javascript/#tooltips-options).
 *
 * ---
 *
 * Not all options are present from Bootstrap to discourage the use of options
 * that will be deprecated in the future.
 *
 * More commonly used options that will be deprecated remain, but are marked as
 * such.
 *
 * @see https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
 */
export interface TooltipCreationOptions {
  /**
   * Whether HTML content is allowed in the tooltip.
   *
   * ---
   *
   * **Warning:** this is a possible XSS attack vector. This option shouldn't
   * be used wherever possible, and will not work when we migrate to CSS-only
   * tooltips.
   *
   * @deprecated
   */
  html?: boolean;
  /**
   * Tooltip position around the target element.
   */
  placement?: 'top' | 'bottom' | 'left' | 'right';
  /**
   * Sets the delay between a trigger state occurring and the tooltip appearing
   * on-screen.
   *
   * ---
   *
   * **Warning:** this option will be removed when we switch to CSS-only
   * tooltips.
   *
   * @deprecated
   */
  delay?: number;
  /**
   * Value used if no `title` attribute is present on the HTML element.
   *
   * If a function is given, it will be called with its `this` reference set to
   * the element that the tooltip is attached to.
   */
  title?: string;
  /**
   * How the tooltip is triggered.
   *
   * Either on `hover`, on `hover focus` (either of the two).
   *
   * ---
   *
   * **Warning:** `manual`, `click` and `focus` on its own are deprecated options
   * which will not be supported in the future.
   */
  trigger?: 'hover' | 'hover focus';
}

/**
 * Creates a tooltip on a jQuery element reference.
 *
 * Returns the same jQuery reference to allow for method chaining.
 */
export type TooltipJQueryFunction = (tooltipOptions?: TooltipCreationOptions | 'destroy' | 'show' | 'hide') => JQuery;
