/// <reference types="mithril" />
import HeaderDropdown, { IHeaderDropdownAttrs } from './HeaderDropdown';
export interface INotificationsDropdown extends IHeaderDropdownAttrs {
}
export default class NotificationsDropdown<CustomAttrs extends INotificationsDropdown = INotificationsDropdown> extends HeaderDropdown<CustomAttrs> {
    static initAttrs(attrs: INotificationsDropdown): void;
    getContent(): JSX.Element;
    goToRoute(): void;
    getUnreadCount(): number;
    getNewCount(): number;
}
