import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';

export default class UserMentionedNotification extends Notification {
  icon() {
    return 'at';
  }

  href() {
    const post = this.props.notification.subject();

    return app.route.discussion(post.discussion(), post.number());
  }

  content() {
    const user = this.props.notification.sender();

    return app.trans('mentions.user_mentioned_notification', {user});
  }
}
