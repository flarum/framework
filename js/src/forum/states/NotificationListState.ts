import PaginatedListState from '../../common/states/PaginatedListState';
import Notification from '../../common/models/Notification';

export default class NotificationListState extends PaginatedListState<Notification> {
  constructor() {
    super(10);

    this.location = { page: 1 };
  }

  get type(): string {
    return 'notifications';
  }

  public isLoading(): boolean {
    return this.isInitialLoading() || this.isLoadingNext();
  }

  /**
   * Load the next page of notification results.
   */
  load(): Promise<void> {
    if (app.session.user.newNotificationCount()) {
      this.pages = [];
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    app.session.user.pushAttributes({ newNotificationCount: 0 });

    return super.loadMore();
  }

  /**
   * Mark all of the notifications as read.
   */
  markAllAsRead() {
    if (this.pages.length === 0) return;

    app.session.user.pushAttributes({ unreadNotificationCount: 0 });

    this.pages.forEach((page) => {
      page.items.forEach((notification) => notification.pushAttributes({ isRead: true }));
    });

    return app.request({
      url: app.forum.attribute('apiUrl') + '/notifications/read',
      method: 'POST',
    });
  }
}
