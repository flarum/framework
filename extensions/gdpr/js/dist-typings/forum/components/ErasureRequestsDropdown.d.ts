/// <reference types="mithril" />
import NotificationsDropdown from 'flarum/forum/components/NotificationsDropdown';
import { IDropdownAttrs } from 'flarum/common/components/Dropdown';
import ErasureRequestsListState from '../states/ErasureRequestsListState';
interface ErasureRequestsDropdownAttrs extends IDropdownAttrs {
    state: ErasureRequestsListState;
}
export default class ErasureRequestsDropdown extends NotificationsDropdown<ErasureRequestsDropdownAttrs> {
    static initAttrs(attrs: ErasureRequestsDropdownAttrs): void;
    getContent(): JSX.Element;
    goToRoute(): void;
    getUnreadCount(): number;
    getNewCount(): number;
}
export {};
