import Component from 'flarum/component';

export default class Notification extends Component {
  view() {
    var notification = this.props.notification;

    return m('div.notification', {
      classNames: !notification.isRead ? 'unread' : '',
      onclick: this.read.bind(this)
    }, this.content());
  }

  content() {
    //
  }

  read() {
    this.props.notification.save({isRead: true});
  }
}
