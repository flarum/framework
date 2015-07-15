import Component from 'flarum/Component';
import NotificationList from 'flarum/components/NotificationList';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage extends Component {
  constructor(...args) {
    super(...args);

    app.current = this;
    app.history.push('notifications');
    app.drawer.hide();
  }

  view() {
    return <div>{NotificationList.component()}</div>;
  }
}
