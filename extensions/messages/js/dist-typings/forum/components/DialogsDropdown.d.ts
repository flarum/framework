/// <reference types="mithril" />
import HeaderDropdown from 'flarum/forum/components/HeaderDropdown';
import type { IHeaderDropdownAttrs } from 'flarum/forum/components/HeaderDropdown';
export interface IDialogsDropdownAttrs extends IHeaderDropdownAttrs {
}
export default class DialogsDropdown<CustomAttrs extends IDialogsDropdownAttrs = IDialogsDropdownAttrs> extends HeaderDropdown<CustomAttrs> {
    protected DialogDropdownList: any;
    static initAttrs(attrs: IDialogsDropdownAttrs): void;
    getContent(): JSX.Element;
    goToRoute(): void;
    getUnreadCount(): number;
    getNewCount(): number;
}
