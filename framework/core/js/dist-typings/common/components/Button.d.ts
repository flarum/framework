import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
export interface IButtonAttrs extends ComponentAttrs {
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
export default class Button<CustomAttrs extends IButtonAttrs = IButtonAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.VnodeDOM<CustomAttrs, this>): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Get the template for the button's content.
     */
    protected getButtonContent(children: Mithril.Children): Mithril.ChildArray;
}
