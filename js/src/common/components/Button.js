import Component from '../Component';
import icon from '../helpers/icon';
import extract from '../utils/extract';
import extractText from '../utils/extractText';
import LoadingIndicator from './LoadingIndicator';

/**
 * The `Button` component defines an element which, when clicked, performs an
 * action. The button may have the following special props:
 *
 * - `icon` The name of the icon class. If specified, the button will be given a
 *   'has-icon' class name.
 * - `disabled` Whether or not the button is disabled. If truthy, the button
 *   will be given a 'disabled' class name, and any `onclick` handler will be
 *   removed.
 * - `loading` Whether or not the button should be in a disabled loading state.
 *
 * All other props will be assigned as attributes on the button element.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item.
 */
export default class Button extends Component {
  view(vnode) {
    const attrs = Object.assign({}, this.attrs);

    attrs.className = attrs.className || '';
    attrs.type = attrs.type || 'button';

    // If a tooltip was provided for buttons without additional content, we also
    // use this tooltip as text for screen readers
    if (attrs.title && !vnode.children) {
      attrs['aria-label'] = attrs.title;
    }

    // If nothing else is provided, we use the textual button content as tooltip
    if (!attrs.title && vnode.children) {
      attrs.title = extractText(vnode.children);
    }

    const iconName = extract(attrs, 'icon');
    if (iconName) attrs.className += ' hasIcon';

    const loading = extract(attrs, 'loading');
    if (attrs.disabled || loading) {
      attrs.className += ' disabled' + (loading ? ' loading' : '');
      delete attrs.onclick;
    }

    return <button {...attrs}>{this.getButtonContent(vnode.children)}</button>;
  }

  /**
   * Get the template for the button's content.
   *
   * @return {*}
   * @protected
   */
  getButtonContent(children) {
    const iconName = this.attrs.icon;

    return [
      iconName && iconName !== true ? icon(iconName, { className: 'Button-icon' }) : '',
      children ? <span className="Button-label">{children}</span> : '',
      this.attrs.loading ? <LoadingIndicator size="tiny" className="LoadingIndicator--inline" /> : '',
    ];
  }
}
