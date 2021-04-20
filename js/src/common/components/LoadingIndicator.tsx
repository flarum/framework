import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';

export interface LoadingIndicatorAttrs extends ComponentAttrs {
  /**
   * Custom classes fro the loading indicator's container.
   */
  className?: string;
  /**
   * Custom classes for the loading indicator's container.
   */
  containerClassName?: string;
  /**
   * Optional size to specify for the loading indicator.
   */
  size?: 'large' | 'medium' | 'small';
  /**
   * Optional attributes to apply to the loading indicator's container.
   */
  containerAttrs?: Partial<ComponentAttrs>;
  inline?: boolean;
  block?: boolean;
}

/**
 * The `LoadingIndicator` component displays a simple CSS-based loading spinner.
 *
 * To set a custom color, use the CSS `color` property.
 *
 * To increase spacing around the spinner, use the CSS `height` property on the
 * spinner's **container**. Applying the `block` attribute handles this for you.
 *
 * To apply a custom size to the loading indicator, set the `--size` and
 * `--thickness` custom properties on the loading indicator itself.
 *
 * If you really want to change how this looks as part of your custom theme,
 * you can override the `border-radius` and `border` then set either a
 * background image, or use `content: "\<glyph>"` (e.g. `content: "\f1ce"`)
 * and `font-family: 'Font Awesome 5 Free'` to set an FA icon if you'd rather.
 *
 * ### Attrs
 *
 * - `containerClassName` Class name(s) to apply to the indicator's parent
 * - `className` Class name(s) to apply to the indicator itself
 * - `size` Size of the loading indicator (small, medium or large)
 * - `containerAttrs` Optional attrs to be applied to the container DOM element
 * - `block` Displays the spinner as a block element with a height of 100px
 * - `inline` Displays the spinner as an inline element, centrally aligned vertically.
 *
 * All other attrs will be assigned as attributes on the DOM element.
 */
export default class LoadingIndicator extends Component<LoadingIndicatorAttrs> {
  view() {
    const { inline = false, block = false, size = 'medium', containerClassName, className, ...attrs } = this.attrs;

    if (inline && block) throw new Error('LoadingIndicator cannot have both `inline` and `block` attributes.');

    const completeClassName = classList('LoadingIndicator', className);
    const completeContainerClassName = classList(
      'LoadingIndicator-container',
      block && 'LoadingIndicator--block',
      inline && 'LoadingIndicator--inline',
      containerClassName
    );

    return (
      <div
        aria-label={app.translator.trans('core.lib.loading_indicator.accessible_label')}
        role="status"
        {...attrs.containerAttrs}
        data-size={size}
        className={completeContainerClassName}
      >
        <div aria-hidden className={completeClassName} {...attrs} />
      </div>
    );
  }
}
