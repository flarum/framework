import PaginatedListState from '../../common/states/PaginatedListState';
import Notification from '../../common/models/Notification';
export default class NotificationListState extends PaginatedListState<Notification> {
    constructor();
    get type(): string;
    /**
     * Load the next page of notification results.
     */
    load(): Promise<void>;
    /**
     * Mark all of the notifications as read.
     */
    markAllAsRead(): Promise<unknown> | undefined;
}
