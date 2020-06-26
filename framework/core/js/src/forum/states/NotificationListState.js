export default class NotificationListState {
  constructor(app) {
    this.app = app;

    this.notificationPages = [];

    this.loading = false;

    this.moreResults = false;
  }

  clear() {
    this.notificationPages = [];
  }

  getNotificationPages() {
    return this.notificationPages;
  }

  isLoading() {
    return this.loading;
  }

  hasMoreResults() {
    return this.moreResults;
  }

  /**
   * Load notifications into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (this.app.session.user.newNotificationCount()) {
      this.notificationPages = [];
    }

    if (this.notificationPages.length > 0) {
      return;
    }

    this.app.session.user.pushAttributes({ newNotificationCount: 0 });

    this.loadMore();
  }

  /**
   * Load the next page of notification results.
   *
   * @public
   */
  loadMore() {
    this.loading = true;
    m.redraw();

    const params = this.notificationPages.length > 0 ? { page: { offset: this.notificationPages.length * 10 } } : null;

    return this.app.store
      .find('notifications', params)
      .then(this.parseResults.bind(this))
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }

  /**
   * Parse results and append them to the notification list.
   *
   * @param {Notification[]} results
   * @return {Notification[]}
   */
  parseResults(results) {
    if (results.length) this.notificationPages.push(results);

    this.moreResults = !!results.payload.links.next;

    return results;
  }

  /**
   * Mark all of the notifications as read.
   */
  markAllAsRead() {
    if (this.notificationPages.length === 0) return;

    this.app.session.user.pushAttributes({ unreadNotificationCount: 0 });

    this.notificationPages.forEach((notifications) => {
      notifications.forEach((notification) => notification.pushAttributes({ isRead: true }));
    });

    this.app.request({
      url: this.app.forum.attribute('apiUrl') + '/notifications/read',
      method: 'POST',
    });
  }
}
