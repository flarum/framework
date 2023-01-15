import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface IDropdownAttrs extends ComponentAttrs {
    /** A class name to apply to the dropdown toggle button. */
    buttonClassName?: string;
    /** A class name to apply to the dropdown menu. */
    menuClassName?: string;
    /** The name of an icon to show in the dropdown toggle button. */
    icon?: string;
    /** The name of an icon to show on the right of the button. */
    caretIcon?: string;
    /** The label of the dropdown toggle button. Defaults to 'Controls'. */
    label: Mithril.Children;
    /** The label used to describe the dropdown toggle button to assistive readers. Defaults to 'Toggle dropdown menu'. */
    accessibleToggleLabel?: string;
    /** An action to take when the dropdown is collapsed. */
    onhide?: () => void;
    /** An action to take when the dropdown is opened. */
    onshow?: () => void;
    lazyDraw?: boolean;
}
/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * The children will be displayed as a list inside the dropdown menu.
 */
export default class Dropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Component<CustomAttrs> {
    protected showing: boolean;
    static initAttrs(attrs: IDropdownAttrs): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Get the template for the button.
     */
    getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any>;
    /**
     * Get the template for the button's content.
     */
    getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray;
    getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any>;
}
