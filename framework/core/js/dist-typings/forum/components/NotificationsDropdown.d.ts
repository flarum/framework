export default class NotificationsDropdown extends Dropdown {
    getButton(): import("mithril").Children;
    getButtonContent(): (false | JSX.Element)[];
    getMenu(): JSX.Element;
    onclick(): void;
    goToRoute(): void;
    getUnreadCount(): number | undefined;
    getNewCount(): number | undefined;
    menuClick(e: any): void;
}
import Dropdown from "../../common/components/Dropdown";
