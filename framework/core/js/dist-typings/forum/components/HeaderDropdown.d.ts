import Dropdown from '../../common/components/Dropdown';
import type { IDropdownAttrs } from '../../common/components/Dropdown';
import type Mithril from 'mithril';
export interface IHeaderDropdownAttrs extends IDropdownAttrs {
    state: any;
}
export default abstract class HeaderDropdown<CustomAttrs extends IHeaderDropdownAttrs = IHeaderDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: IHeaderDropdownAttrs): void;
    getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any>;
    getButtonContent(): Mithril.ChildArray;
    getMenu(): JSX.Element;
    menuClick(e: MouseEvent): void;
    onclick(): void;
    abstract getNewCount(): number;
    abstract getUnreadCount(): number;
    abstract getContent(): Mithril.Children;
    abstract goToRoute(): void;
}
