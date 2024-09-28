import Mithril from 'mithril';
import Select, { ISelectAttrs } from './Select';
export interface IMultiSelectAttrs extends ISelectAttrs {
}
export default class MultiSelect<CustomAttrs extends IMultiSelectAttrs = IMultiSelectAttrs> extends Select<CustomAttrs> {
    protected selected: string[];
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    input(): JSX.Element;
    select(value: string): void;
    unselect(value: string): void;
    toggle(value: string, e: MouseEvent): void;
}
