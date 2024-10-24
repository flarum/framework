/// <reference types="mithril" />
import HeaderDropdown from 'flarum/forum/components/HeaderDropdown';
import type { IHeaderDropdownAttrs } from 'flarum/forum/components/HeaderDropdown';
export interface IFlagsDropdownAttrs extends IHeaderDropdownAttrs {
}
export default class FlagsDropdown<CustomAttrs extends IFlagsDropdownAttrs = IFlagsDropdownAttrs> extends HeaderDropdown<CustomAttrs> {
    static initAttrs(attrs: IFlagsDropdownAttrs): void;
    getContent(): JSX.Element;
    goToRoute(): void;
    getUnreadCount(): number;
    getNewCount(): number;
}
