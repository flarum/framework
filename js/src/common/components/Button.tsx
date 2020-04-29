import Component, { ComponentProps } from '../Component';
import icon from '../helpers/icon';
import extract from '../utils/extract';
import extractText from '../utils/extractText';
import LoadingIndicator from './LoadingIndicator';

export interface ButtonProps extends ComponentProps {
    /**
     * A tooltip for the button.
     */
    title?: string;

    /**
     * An html type attribute for the button.
     */
    type?: string;

    /**
     * The name of the icon class. If specified, the button will be given a
     * 'has-icon' class name.
     */
    icon?: string;

    /**
     * Whether or not the button should be in a disabled loading state.
     */
    loading?: boolean;

    /**
     * Whether or not the button is disabled. If truthy, the button
     * will be given a 'disabled' class name, and any `onclick` handler will be
     * removed.
     */
    disabled?: boolean;

    /**
     * A callback to run when the button is clicked.
     */
    onclick?: Function;
}

/**
 * The `Button` component defines an element which, when clicked, performs an
 * action.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item.
 */
export default class Button<T extends ButtonProps = ButtonProps> extends Component<ButtonProps> {
    view() {
        const attrs: ButtonProps = { ...this.props };

        const children = extract(attrs, 'children');

        attrs.className = attrs.className || '';
        attrs.type = attrs.type || 'button';

        // If a tooltip was provided for buttons without additional content, we also
        // use this tooltip as text for screen readers
        if (attrs.title && !children) {
            attrs['aria-label'] = attrs.title;
        }

        // If nothing else is provided, we use the textual button content as tooltip
        if (!attrs.title && children) {
            attrs.title = extractText(this.props.children);
        }

        const iconName = extract(attrs, 'icon');
        if (iconName) attrs.className += ' hasIcon';

        const loading = extract(attrs, 'loading');
        if (attrs.disabled || loading) {
            attrs.className = classNames(attrs.className, 'disabled', loading && 'loading');
            delete attrs.onclick;
        }

        return <button {...attrs}>{this.getButtonContent(iconName, loading, children)}</button>;
    }

    /**
     * Get the template for the button's content.
     */
    protected getButtonContent(iconName?: string | boolean, loading?: boolean, children?: any): any[] {
        return [
            iconName && iconName !== true ? icon(iconName, { className: 'Button-icon' }) : '',
            children ? <span className="Button-label">{children}</span> : '',
            loading ? LoadingIndicator.component({ size: 'tiny', className: 'LoadingIndicator--inline' }) : '',
        ];
    }
}
