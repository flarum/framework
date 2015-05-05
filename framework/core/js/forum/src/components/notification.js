import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/human-time';

export default class Notification extends Component {
  view(args) {
    var notification = this.props.notification;

    return m('div.notification.notification-'+notification.contentType(), {
      classNames: !notification.isRead() ? 'unread' : '',
      onclick: this.read.bind(this)
    }, m('a', {href: args.href, config: args.config}, [
      avatar(notification.sender()),
      m('h3.notification-title', args.title),
      m('div.notification-info', [
        icon(args.icon), ' ',
        args.content, ' ',
        humanTime(notification.time())
      ])
    ]));
  }

  read() {
    this.props.notification.save({isRead: true});
  }
}
