import Component from 'flarum/Component';
import listItems from 'flarum/helpers/listItems';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Discussion from 'flarum/models/Discussion';

/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the notifications are loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    this.load();
  }

  view() {
    const groups = [];

    if (app.cache.notifications) {
      const discussions = {};

      // Build an array of discussions which the notifications are related to,
      // and add the notifications as children.
      app.cache.notifications.forEach(notification => {
        const subject = notification.subject();

        // Get the discussion that this notification is related to. If it's not
        // directly related to a discussion, it may be related to a post or
        // other entity which is related to a discussion.
        let discussion;
        if (subject instanceof Discussion) discussion = subject;
        else if (subject.discussion) discussion = subject.discussion();

        // If the notification is not related to a discussion directly or
        // indirectly, then we will assign it to a neutral group.
        const key = discussion ? discussion.id() : 0;
        discussions[key] = discussions[key] || {discussion: discussion, notifications: []};
        discussions[key].notifications.push(notification);

        if (groups.indexOf(discussions[key]) === -1) {
          groups.push(discussions[key]);
        }
      });
    }

    return (
      <div className="notification-list">
        <div className="notifications-header">
          <div className="primary-control">
            {Button.component({
              className: 'btn btn-icon btn-link btn-sm',
              icon: 'check',
              title: 'Mark All as Read',
              onclick: this.markAllAsRead.bind(this)
            })}
          </div>

          <h4 className="title-control">Notifications</h4>
        </div>

        <div className="notifications-content">
          {groups.length
            ? groups.map(group => {
              const badges = group.discussion && group.discussion.badges().toArray();

              return (
                <div className="notification-group">
                  {group.discussion
                    ? (
                      <a className="notification-group-header"
                        href={app.route.discussion(group.discussion)}
                        config={m.route}>
                        {badges && badges.length ? <ul className="badges">{listItems(badges)}</ul> : ''}
                        {group.discussion.title()}
                      </a>
                    ) : (
                      <div className="notification-group-header">
                        {app.forum.attribute('title')}
                      </div>
                    )}

                  <ul className="notification-group-list">
                    {group.notifications.map(notification => {
                      const NotificationComponent = app.notificationComponents[notification.contentType()];
                      return NotificationComponent ? <li>{NotificationComponent.component({notification})}</li> : '';
                    })}
                  </ul>
                </div>
              );
            })
            : !this.loading
              ? <div className="no-notifications">No Notifications</div>
              : LoadingIndicator.component({className: 'loading-indicator-block'})}
        </div>
      </div>
    );
  }

  /**
   * Load notifications into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (app.cache.notifications && !app.session.user.unreadNotificationsCount()) {
      return;
    }

    this.loading = true;
    m.redraw();

    app.store.find('notifications').then(notifications => {
      app.session.user.pushAttributes({unreadNotificationsCount: 0});
      app.cache.notifications = notifications.sort((a, b) => b.time() - a.time());

      this.loading = false;
      m.redraw();
    });
  }

  /**
   * Mark all of the notifications as read.
   */
  markAllAsRead() {
    if (!app.cache.notifications) return;

    app.cache.notifications.forEach(notification => {
      if (!notification.isRead()) {
        notification.save({isRead: true});
      }
    });
  }
}
