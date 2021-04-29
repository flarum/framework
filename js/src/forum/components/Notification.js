import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/helpers/humanTime';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import classList from '../../common/utils/classList';

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
        className={classList('Notification', `Notification--${notification.contentType()}`, [!notification.isRead() && 'unread'])}
        href={href}
        external={href.includes('://')}
        onclick={this.markAsRead.bind(this)}
      >
        {avatar(notification.fromUser())}
        {icon(this.icon(), { className: 'Notification-icon' })}
        <span className="Notification-title">
          <span className="Notification-content">{this.content()}</span>
          <span className="Notification-title-spring" />
          {humanTime(notification.createdAt())}
        </span>
        {!notification.isRead() && (
          <Button
            className="Notification-action Button Button--link"
            icon="fas fa-check"
            title={app.translator.trans('core.forum.notifications.mark_as_read_tooltip')}
            onclick={(e) => {
              e.preventDefault();
              e.stopPropagation();

              this.markAsRead();
            }}
          />
        )}
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
