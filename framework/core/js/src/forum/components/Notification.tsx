import app from '../../forum/app';
import type NotificationModel from '../../common/models/Notification';
import Component, { ComponentAttrs } from '../../common/Component';
import Button from '../../common/components/Button';
import classList from '../../common/utils/classList';
import type Mithril from 'mithril';
import HeaderListItem from './HeaderListItem';
import ItemList from '../../common/utils/ItemList';
import Avatar from '../../common/components/Avatar';

export interface INotificationAttrs extends ComponentAttrs {
  notification: NotificationModel;
}

/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 */
export default abstract class Notification<CustomAttrs extends INotificationAttrs = INotificationAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const notification = this.attrs.notification;
    const href = this.href() ?? '';
    const fromUser = notification.fromUser();

    return (
      <HeaderListItem
        className={classList('Notification', `Notification--${notification.contentType()}`, [!notification.isRead() && 'unread'])}
        avatar={<Avatar user={fromUser || null} />}
        icon={this.icon()}
        content={this.content()}
        excerpt={this.excerpt()}
        datetime={notification.createdAt()}
        href={href}
        onclick={this.markAsRead.bind(this)}
        actions={this.actionItems().toArray()}
      />
    );
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (!this.attrs.notification.isRead()) {
      items.add(
        'markAsRead',
        <Button
          className="Notification-action Button Button--link"
          icon="fas fa-check"
          aria-label={app.translator.trans('core.forum.notifications.mark_as_read_tooltip')}
          onclick={(e: Event) => {
            e.preventDefault();
            e.stopPropagation();

            this.markAsRead();
          }}
        />,
        100
      );
    }

    return items;
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
