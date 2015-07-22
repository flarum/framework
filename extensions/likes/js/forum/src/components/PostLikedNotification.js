import Notification from 'flarum/components/Notification';

export default class PostLikedNotification extends Notification {
  icon() {
    return 'thumbs-o-up';
  }

  href() {
    return app.route.post(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;
    const post = notification.subject();
    const user = notification.sender();
    const auc = notification.additionalUnreadCount();

    return app.trans('likes.post_liked_notification', {
      user,
      username: auc ? punctuate([
        username(user),
        app.trans('core.others', {count: auc})
      ]) : undefined,
      number: post.number()
    });
  }
}
