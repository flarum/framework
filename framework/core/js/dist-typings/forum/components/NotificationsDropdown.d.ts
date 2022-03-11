export default class NotificationsDropdown extends Dropdown {
    onclick(): void;
    goToRoute(): void;
    getUnreadCount(): number | undefined;
    getNewCount(): number | undefined;
    menuClick(e: any): void;
}
import Dropdown from "../../common/components/Dropdown";
