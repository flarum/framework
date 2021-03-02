import Dropdown from '../../common/components/Dropdown';
import icon from '../../common/helpers/icon';
import NotificationList from './NotificationList';

export default class NotificationsDropdown extends Dropdown {
  static initAttrs(attrs) {
    attrs.className = attrs.className || 'NotificationsDropdown';
    attrs.buttonClassName = attrs.buttonClassName || 'Button Button--flat';
    attrs.menuClassName = attrs.menuClassName || 'Dropdown-menu--right';
    attrs.label = attrs.label || app.translator.trans('core.forum.notifications.tooltip');
    attrs.icon = attrs.icon || 'fas fa-bell';

    super.initAttrs(attrs);
  }

  getButton() {
    const newNotifications = this.getNewCount();
    const vdom = super.getButton();

    vdom.attrs.title = this.attrs.label;

    vdom.attrs.className += newNotifications ? ' new' : '';
    vdom.attrs.onclick = this.onclick.bind(this);

    return vdom;
  }

  getButtonContent() {
    const unread = this.getUnreadCount();

    return [
      icon(this.attrs.icon, { className: 'Button-icon' }),
      unread ? <span className="NotificationsDropdown-unread">{unread}</span> : '',
      <span className="Button-label">{this.attrs.label}</span>,
    ];
  }

  getMenu() {
    return (
      <div className={'Dropdown-menu ' + this.attrs.menuClassName} onclick={this.menuClick.bind(this)}>
        {this.showing ? NotificationList.component({ state: this.attrs.state }) : ''}
      </div>
    );
  }

  onclick() {
    if (app.drawer.isOpen()) {
      this.goToRoute();
    } else {
      this.attrs.state.load();
    }
  }

  goToRoute() {
    m.route.set(app.route('notifications'));
  }

  getUnreadCount() {
    return app.session.user.unreadNotificationCount();
  }

  getNewCount() {
    return app.session.user.newNotificationCount();
  }

  menuClick(e) {
    // Don't close the notifications dropdown if the user is opening a link in a
    // new tab or window.
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.which === 2) e.stopPropagation();
  }
}
