import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import type Mithril from 'mithril';
export interface INotificationsDropdown extends IDropdownAttrs {
}
export default class NotificationsDropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: INotificationsDropdown): void;
    getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any>;
    getButtonContent(): Mithril.ChildArray;
    getMenu(): JSX.Element;
    onclick(): void;
    goToRoute(): void;
    getUnreadCount(): number | undefined;
    getNewCount(): number | undefined;
    menuClick(e: MouseEvent): void;
}
