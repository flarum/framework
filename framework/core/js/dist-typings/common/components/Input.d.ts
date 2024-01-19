import Component from '../Component';
import Stream from '../utils/Stream';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface IInputAttrs extends ComponentAttrs {
    className?: string;
    prefixIcon?: string;
    clearable?: boolean;
    clearLabel?: string;
    loading?: boolean;
    inputClassName?: string;
    onchange?: (value: string) => void;
    value?: string;
    stream?: Stream<string>;
    type?: string;
    ariaLabel?: string;
    placeholder?: string;
    readonly?: boolean;
    disabled?: boolean;
    renderInput?: (attrs: any) => Mithril.Children;
    inputAttrs?: {
        className?: string;
        [key: string]: any;
    };
}
export default class Input<CustomAttrs extends IInputAttrs = IInputAttrs> extends Component<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    input({ inputClassName, value, inputAttrs }: any): Mithril.Children | JSX.Element;
    onchange(value: string): void;
    clear(): void;
}
