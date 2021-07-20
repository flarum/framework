import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import extract from '../utils/extract';
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
   * Accessible text for the button. This should always be present if the button only
   * contains an icon.
   *
   * The textual content of this attribute is passed to the DOM element as `aria-label`.
   */
  title?: string | Mithril.ChildArray;
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
    const attrs = Object.assign({}, this.attrs);

    attrs.type = attrs.type || 'button';

    // If a tooltip was provided for buttons without additional content, we also
    // use this tooltip as text for screen readers
    if (attrs.title && !vnode.children) {
      attrs['aria-label'] = attrs.title;
    }

    // If given a translation object, extract the text.
    if (typeof attrs.title === 'object') {
      attrs.title = extractText(attrs.title);
    }

    // If nothing else is provided, we use the textual button content as tooltip
    if (!attrs.title && vnode.children) {
      attrs.title = extractText(vnode.children);
    }

    const iconName = extract(attrs, 'icon');

    const loading = extract(attrs, 'loading');
    if (attrs.disabled || loading) {
      delete attrs.onclick;
    }

    attrs.className = classList([attrs.className, iconName && 'hasIcon', (attrs.disabled || loading) && 'disabled', loading && 'loading']);

    return <button {...attrs}>{this.getButtonContent(vnode.children)}</button>;
  }

  /**
   * Get the template for the button's content.
   */
  protected getButtonContent(children: Mithril.Children): Mithril.ChildArray {
    const iconName = this.attrs.icon;

    return [
      iconName && iconName !== true ? icon(iconName, { className: 'Button-icon' }) : '',
      children ? <span className="Button-label">{children}</span> : '',
      this.attrs.loading ? <LoadingIndicator size="small" display="inline" /> : '',
    ];
  }
}
