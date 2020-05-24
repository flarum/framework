export default class NotificationListState {
  constructor() {
    this.notificationPages = [];

    /**
     * Whether or not the notifications are loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    /**
     * Whether or not there are more results that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;
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
    if (app.session.user.newNotificationCount()) {
      this.notificationPages = [];
    }

    if (this.notificationPages.length > 0) {
      return;
    }

    app.session.user.pushAttributes({ newNotificationCount: 0 });

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

    return app.store
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

    app.session.user.pushAttributes({ unreadNotificationCount: 0 });

    this.notificationPages.forEach((notifications) => {
      notifications.forEach((notification) => notification.pushAttributes({ isRead: true }));
    });

    app.request({
      url: app.forum.attribute('apiUrl') + '/notifications/read',
      method: 'POST',
    });
  }
}
