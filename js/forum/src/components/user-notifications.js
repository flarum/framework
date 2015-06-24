import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import DropdownButton from 'flarum/components/dropdown-button';
import NotificationList from 'flarum/components/notification-list';

export default class UserNotifications extends Component {
  constructor(props) {
    super(props);

    this.showing = m.prop(false);
  }

  view() {
    var user = this.props.user;

    return DropdownButton.component({
      className: 'notifications',
      buttonClass: 'btn btn-default btn-rounded btn-naked btn-icon'+(user.unreadNotificationsCount() ? ' unread' : ''),
      menuClass: 'pull-right',
      buttonContent: [
        m('span.notifications-icon', user.unreadNotificationsCount() || icon('bell icon-glyph')),
        m('span.label', 'Notifications')
      ],
      buttonClick: (e) => {
        if ($('body').hasClass('drawer-open')) {
          m.route(app.route('notifications'));
        } else {
          this.showing(true);
        }
      },
      menuContent: this.showing() ? NotificationList.component() : []
    });
  }
}
