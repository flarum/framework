/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../Component';
export type Option = {
    label: string;
    disabled?: boolean | ((value: any) => boolean);
    tooltip?: string;
};
export interface ISelectAttrs extends ComponentAttrs {
    options: Record<string, string | Option>;
    onchange?: (value: any) => void;
    value?: any;
    disabled?: boolean;
    wrapperAttrs?: Record<string, string>;
}
/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following attrs:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 * - `wrapperAttrs` A map of attrs to be passed to the DOM element wrapping the `<select>`
 *
 * Other attributes are passed directly to the `<select>` element rendered to the DOM.
 */
export default class Select<CustomAttrs extends ISelectAttrs = ISelectAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    input(): JSX.Element;
}
