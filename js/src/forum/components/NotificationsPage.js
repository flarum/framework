import Page from '../../common/components/Page';
import NotificationList from './NotificationList';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    app.history.push('notifications');

    app.notifications.load();

    this.bodyClass = 'App--notifications';
  }

  view() {
    return (
      <div className="NotificationsPage">
        <NotificationList state={app.notifications}></NotificationList>
      </div>
    );
  }
}
