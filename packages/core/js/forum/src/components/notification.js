import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/human-time';
import { dasherize } from 'flarum/utils/string';

export default class Notification extends Component {
  view(args) {
    var notification = this.props.notification;

    return m('div.notification.notification-'+dasherize(notification.contentType()), {
      className: !notification.isRead() ? 'unread' : '',
      onclick: this.read.bind(this)
    }, m('a', {href: args.href, config: args.config || m.route}, [
      avatar(notification.sender()), ' ',
      icon(args.icon+' icon'), ' ',
      m('span.content', args.content), ' ',
      humanTime(notification.time())
    ]));
  }

  read() {
    this.props.notification.save({isRead: true});
  }
}
