import app from '../../forum/app';
import type NotificationModel from '../../common/models/Notification';
import Component, { ComponentAttrs } from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/helpers/humanTime';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import classList from '../../common/utils/classList';
import type Mithril from 'mithril';

export interface INotificationAttrs extends ComponentAttrs {
  notification: NotificationModel;
}

// TODO [Flarum 2.0]: Remove `?.` from abstract function calls.

/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 */
export default abstract class Notification<CustomAttrs extends INotificationAttrs = INotificationAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const notification = this.attrs.notification;
    const href = this.href?.() ?? '';

    const fromUser = notification.fromUser();

    return (
      <Link
        className={classList('Notification', `Notification--${notification.contentType()}`, [!notification.isRead() && 'unread'])}
        href={href}
        external={href.includes('://')}
        onclick={this.markAsRead.bind(this)}
      >
        {avatar(fromUser || null)}
        {icon(this.icon?.(), { className: 'Notification-icon' })}
        <span className="Notification-title">
          <span className="Notification-content">{this.content?.()}</span>
          <span className="Notification-title-spring" />
          {humanTime(notification.createdAt())}
        </span>
        {!notification.isRead() && (
          <Button
            className="Notification-action Button Button--link"
            icon="fas fa-check"
            title={app.translator.trans('core.forum.notifications.mark_as_read_tooltip')}
            onclick={(e: Event) => {
              e.preventDefault();
              e.stopPropagation();

              this.markAsRead();
            }}
          />
        )}
        <div className="Notification-excerpt">{this.excerpt?.()}</div>
      </Link>
    );
  }

  /**
   * Get the name of the icon that should be displayed in the notification.
   */
  abstract icon(): string;

  /**
   * Get the URL that the notification should link to.
   */
  abstract href(): string;

  /**
   * Get the content of the notification.
   */
  abstract content(): Mithril.Children;

  /**
   * Get the excerpt of the notification.
   */
  abstract excerpt(): Mithril.Children;

  /**
   * Mark the notification as read.
   */
  markAsRead() {
    if (this.attrs.notification.isRead()) return;

    app.session.user?.pushAttributes({ unreadNotificationCount: (app.session.user.unreadNotificationCount() ?? 1) - 1 });

    this.attrs.notification.save({ isRead: true });
  }
}
