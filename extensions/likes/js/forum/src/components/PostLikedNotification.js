import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import punctuateSeries from 'flarum/helpers/punctuateSeries';

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

    return app.translator.trans('flarum-likes.forum.post_liked_notification', {
      user,
      username: auc ? punctuateSeries([
        username(user),
        app.translator.trans('flarum-likes.forum.others', {count: auc})
      ]) : undefined
    });
  }

  excerpt() {
    return this.props.notification.subject().contentPlain();
  }
}
