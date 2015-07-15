import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/humanTime';

/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 *
 * ### Props
 *
 * - `notification`
 *
 * @abstract
 */
export default class Notification extends Component {
  view() {
    const notification = this.props.notification;
    const href = this.href();

    return (
      <div className={'notification notification-' + notification.contentType() + ' ' + (!notification.isRead() ? 'unread' : '')}
        onclick={this.markAsRead.bind(this)}>
        <a href={href} config={href.indexOf('://') === -1 ? m.route : undefined}>
          {avatar(notification.sender())}
          {icon(this.icon(), {className: 'icon'})}
          <span className="content">{this.content()}</span>
          {humanTime(notification.time())}
        </a>
      </div>
    );
  }

  /**
   * Get the name of the icon that should be displayed in the notification.
   *
   * @return {String}
   * @abstract
   */
  icon() {
  }

  /**
   * Get the URL that the notification should link to.
   *
   * @return {String}
   * @abstract
   */
  href() {
  }

  /**
   * Get the content of the notification.
   *
   * @return {VirtualElement}
   * @abstract
   */
  content() {
  }

  /**
   * Mark the notification as read.
   */
  markAsRead() {
    this.props.notification.save({isRead: true});
  }
}
