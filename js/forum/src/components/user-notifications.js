import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import username from 'flarum/helpers/username';
import DropdownButton from 'flarum/components/dropdown-button';
import ActionButton from 'flarum/components/action-button';
import ItemList from 'flarum/utils/item-list';
import Separator from 'flarum/components/separator';
import LoadingIndicator from 'flarum/components/loading-indicator';
import Discussion from 'flarum/models/discussion';

export default class UserNotifications extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
  }

  view() {
    var user = this.props.user;

    var groups = [];
    if (app.cache.notifications) {
      var groupsObject = {};
      app.cache.notifications.forEach(notification => {
        var subject = notification.subject();
        var discussion = subject instanceof Discussion ? subject : (subject.discussion && subject.discussion());
        var key = discussion ? discussion.id() : 0;
        groupsObject[key] = groupsObject[key] || {discussion: discussion, notifications: []};
        groupsObject[key].notifications.push(notification);
      });
      for (var i in groupsObject) {
        groups.push(groupsObject[i]);
      }
    }

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
        m('div.notifications-content', groups.length
          ? groups.map(group => {
            return m('div.notification-group', [
              group.discussion ? m('a.notification-group-header', {
                href: app.route.discussion(group.discussion),
                config: m.route
              }, group.discussion.title()) : m('div.notification-group-header', app.config['forum_title']),
              m('ul.notifications-list', group.notifications.map(notification => {
                var NotificationComponent = app.notificationComponentRegistry[notification.contentType()];
                return NotificationComponent ? m('li', NotificationComponent.component({notification})) : '';
              }))
            ])
          })
          : (!this.loading() ? m('div.no-notifications', 'No Notifications') : '')),
        this.loading() ? LoadingIndicator.component() : ''
      ]
    });
  }

  load() {
    if (!app.cache.notifications || this.props.user.unreadNotificationsCount()) {
      var component = this;
      this.loading(true);
      m.redraw();
      app.store.find('notifications').then(notifications => {
        this.props.user.pushData({unreadNotificationsCount: 0});
        this.loading(false);
        app.cache.notifications = notifications.sort((a, b) => b.time() - a.time());
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
