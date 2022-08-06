import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface ICheckboxAttrs extends ComponentAttrs {
    state?: boolean;
    loading?: boolean;
    disabled?: boolean;
    onchange: (checked: boolean, component: Checkbox<this>) => void;
}
/**
 * The `Checkbox` component defines a checkbox input.
 *
 * ### Attrs
 *
 * - `state` Whether or not the checkbox is checked.
 * - `className` The class name for the root element.
 * - `disabled` Whether or not the checkbox is disabled.
 * - `loading` Whether or not the checkbox is loading.
 * - `onchange` A callback to run when the checkbox is checked/unchecked.
 * - `children` A text label to display next to the checkbox.
 */
export default class Checkbox<CustomAttrs extends ICheckboxAttrs = ICheckboxAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    /**
     * Get the template for the checkbox's display (tick/cross icon).
     */
    protected getDisplay(): Mithril.Children;
    /**
     * Run a callback when the state of the checkbox is changed.
     */
    protected onchange(checked: boolean): void;
}
