import Component, { type ComponentAttrs } from '../Component';
import Mithril from 'mithril';
export declare type Option = {
    label: string;
    disabled?: boolean | ((value: string[]) => boolean);
    tooltip?: string;
};
export interface IMultiSelectAttrs extends ComponentAttrs {
    options: Record<string, string | Option>;
    onchange?: (value: string[]) => void;
    value?: string[];
    disabled?: boolean;
    wrapperAttrs?: Record<string, string>;
}
/**
 * The `MultiSelect` component displays an input with selected elements.
 * With a dropdown to select multiple options.
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 * - `wrapperAttrs` A map of attrs to be passed to the DOM element wrapping the input.
 *
 * Other attributes are passed directly to the input element rendered to the DOM.
 */
export default class MultiSelect<CustomAttrs extends IMultiSelectAttrs = IMultiSelectAttrs> extends Component<CustomAttrs> {
    protected selected: string[];
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    select(value: string): void;
    unselect(value: string): void;
    toggle(value: string, e: MouseEvent): void;
}
