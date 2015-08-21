import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import punctuate from 'flarum/helpers/punctuate';

export default class PostLikedNotification extends Notification {
  icon() {
    return 'thumbs-o-up';
  }

  href() {
    return app.route.post(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;
    const user = notification.sender();
    const auc = notification.additionalUnreadCount();

    return app.trans('likes.post_liked_notification', {
      user,
      username: auc ? punctuate([
        username(user),
        app.trans('likes.others', {count: auc})
      ]) : undefined
    });
  }

  excerpt() {
    return this.props.notification.subject().contentPlain();
  }
}
