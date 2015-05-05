import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import username from 'flarum/helpers/username';
import DropdownButton from 'flarum/components/dropdown-button';
import ActionButton from 'flarum/components/action-button';
import ItemList from 'flarum/utils/item-list';
import Separator from 'flarum/components/separator';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default class UserNotifications extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
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
      buttonClick: this.load.bind(this),
      menuContent: [
        m('div.notifications-header', [
          ActionButton.component({
            className: 'btn btn-icon btn-link btn-sm',
            icon: 'check',
            title: 'Mark All as Read',
            onclick: this.markAllAsRead.bind(this)
          }),
          m('h4', 'Notifications')
        ]),
        m('ul.notifications-list', app.cache.notifications
          ? app.cache.notifications.map(notification => {
            var NotificationComponent = app.notificationComponentRegistry[notification.contentType()];
            return NotificationComponent ? m('li', NotificationComponent.component({notification})) : '';
          })
          : (!this.loading() ? m('li.no-notifications', 'No Notifications') : '')),
        this.loading() ? LoadingIndicator.component() : ''
      ]
    });
  }

  load() {
    if (!app.cache.notifications) {
      var component = this;
      this.loading(true);
      m.redraw();
      app.store.find('notifications').then(notifications => {
        this.props.user.pushData({unreadNotificationsCount: 0});
        this.loading(false);
        app.cache.notifications = notifications;
        m.redraw();
      })
    }
  }

  markAllAsRead() {
    app.cache.notifications.forEach(function(notification) {
      if (!notification.isRead()) {
        notification.save({isRead: true});
      }
    })
  }
}
