import Component from 'flarum/component';
import NotificationList from 'flarum/components/notification-list';

export default class NotificationsPage extends Component {
  constructor(props) {
    super(props);

    app.current = this;
    app.history.push('notifications');
    app.drawer.hide();
  }

  view() {
    return m('div', NotificationList.component());
  }
}
