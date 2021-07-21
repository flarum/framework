import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import fireDebugWarning from '../helpers/fireDebugWarning';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import extractText from '../utils/extractText';
import LoadingIndicator from './LoadingIndicator';

export interface ButtonAttrs extends ComponentAttrs {
  /**
   * Class(es) of an optional icon to be rendered within the button.
   *
   * If provided, the button will gain a `has-icon` class.
   */
  icon?: string;
  /**
   * Disables button from user input.
   *
   * Default: `false`
   */
  disabled?: boolean;
  /**
   * Show a loading spinner within the button.
   *
   * If `true`, also disables the button.
   *
   * Default: `false`
   */
  loading?: boolean;
  /**
   * **DEPRECATED:** Please use the `aria-label` attribute instead. For tooltips, use
   * the `<Tooltip>` component.
   *
   * Accessible text for the button. This should always be present if the button only
   * contains an icon.
   *
   * The textual content of this attribute is passed to the DOM element as `aria-label`.
   *
   * @deprecated
   */
  title?: string | Mithril.ChildArray;
  /**
   * Accessible text for the button. This should always be present if the button only
   * contains an icon.
   *
   * The textual content of this attribute is passed to the DOM element as `aria-label`.
   */
  'aria-label'?: string | Mithril.ChildArray;
  /**
   * Button type.
   *
   * Default: `"button"`
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#attr-type
   */
  type?: string;
}

/**
 * The `Button` component defines an element which, when clicked, performs an
 * action.
 *
 * Other attrs will be assigned as attributes on the `<button>` element.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item. Common
 * styles can be applied by providing `className="Button"` to the Button component.
 */
export default class Button extends Component<ButtonAttrs> {
  view(vnode: Mithril.Vnode<ButtonAttrs, never>) {
    let { type, title, 'aria-label': ariaLabel, icon: iconName, disabled, loading, className, class: _class, ...attrs } = this.attrs;

    // If no `type` attr provided, set to "button"
    type ||= 'button';

    // Use `title` attribute as `aria-label` if none provided
    ariaLabel ||= title;

    // If given a translation object, extract the text.
    if (typeof ariaLabel === 'object') {
      ariaLabel = extractText(ariaLabel);
    }

    if (disabled || loading) {
      delete attrs.onclick;
    }

    className = classList(_class, className, {
      hasIcon: iconName,
      disabled: disabled || loading,
      loading: loading,
    });

    if (!ariaLabel && !extractText(vnode.children) && !this.element?.getAttribute?.('aria-label')) {
      fireDebugWarning(
        '[Flarum Accessibility Warning] This button has no content and does not have any accessible label. This means that screen-readers will not be able to interpret its meaning. Consider providing accessible text via the `aria-label` attribute.\n\nLearn more: https://web.dev/button-name',
        this.element
      );
    }

    const buttonAttrs = {
      disabled,
      className,
      type,
      'aria-label': ariaLabel,
      ...attrs,
    };

    return <button {...buttonAttrs}>{this.getButtonContent(vnode.children)}</button>;
  }

  /**
   * Get the template for the button's content.
   */
  protected getButtonContent(children: Mithril.Children): Mithril.ChildArray {
    const iconName = this.attrs.icon;

    return [
      iconName && icon(iconName, { className: 'Button-icon' }),
      children && <span className="Button-label">{children}</span>,
      this.attrs.loading && <LoadingIndicator size="small" display="inline" />,
    ];
  }
}
