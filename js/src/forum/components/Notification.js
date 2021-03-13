import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/helpers/humanTime';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';

/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 *
 * ### Attrs
 *
 * - `notification`
 *
 * @abstract
 */
export default class Notification extends Component {
  view() {
    const notification = this.attrs.notification;
    const href = this.href();

    return (
      <Link
        className={'Notification Notification--' + notification.contentType() + ' ' + (!notification.isRead() ? 'unread' : '')}
        href={href}
        external={href.includes('://')}
        onclick={this.markAsRead.bind(this)}
      >
        {!notification.isRead() &&
          Button.component({
            className: 'Notification-action Button Button--icon Button--link',
            icon: 'fas fa-check',
            title: app.translator.trans('core.forum.notifications.mark_as_read_tooltip'),
            onclick: (e) => {
              e.preventDefault();
              e.stopPropagation();

              this.markAsRead();
            },
          })}
        {avatar(notification.fromUser())}
        {icon(this.icon(), { className: 'Notification-icon' })}
        <span className="Notification-content">{this.content()}</span>
        {humanTime(notification.createdAt())}
        <div className="Notification-excerpt">{this.excerpt()}</div>
      </Link>
    );
  }

  /**
   * Get the name of the icon that should be displayed in the notification.
   *
   * @return {String}
   * @abstract
   */
  icon() {}

  /**
   * Get the URL that the notification should link to.
   *
   * @return {String}
   * @abstract
   */
  href() {}

  /**
   * Get the content of the notification.
   *
   * @return {VirtualElement}
   * @abstract
   */
  content() {}

  /**
   * Get the excerpt of the notification.
   *
   * @return {VirtualElement}
   * @abstract
   */
  excerpt() {}

  /**
   * Mark the notification as read.
   */
  markAsRead() {
    if (this.attrs.notification.isRead()) return;

    app.session.user.pushAttributes({ unreadNotificationCount: app.session.user.unreadNotificationCount() - 1 });

    this.attrs.notification.save({ isRead: true });
  }
}
