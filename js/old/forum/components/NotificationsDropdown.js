import Dropdown from '../../common/components/Dropdown';
import icon from '../../common/helpers/icon';
import NotificationList from './NotificationList';

export default class NotificationsDropdown extends Dropdown {
  static initProps(props) {
    props.className = props.className || 'NotificationsDropdown';
    props.buttonClassName = props.buttonClassName || 'Button Button--flat';
    props.menuClassName = props.menuClassName || 'Dropdown-menu--right';
    props.label = props.label || app.translator.trans('core.forum.notifications.tooltip');
    props.icon = props.icon || 'fas fa-bell';

    super.initProps(props);
  }

  init() {
    super.init();

    this.list = new NotificationList();
  }

  getButton() {
    const newNotifications = this.getNewCount();
    const vdom = super.getButton();

    vdom.attrs.title = this.props.label;

    vdom.attrs.className += (newNotifications ? ' new' : '');
    vdom.attrs.onclick = this.onclick.bind(this);

    return vdom;
  }

  getButtonContent() {
    const unread = this.getUnreadCount();

    return [
      icon(this.props.icon, {className: 'Button-icon'}),
      unread ? <span className="NotificationsDropdown-unread">{unread}</span> : '',
      <span className="Button-label">{this.props.label}</span>
    ];
  }

  getMenu() {
    return (
      <div className={'Dropdown-menu ' + this.props.menuClassName} onclick={this.menuClick.bind(this)}>
        {this.showing ? this.list.render() : ''}
      </div>
    );
  }

  onclick() {
    if (app.drawer.isOpen()) {
      this.goToRoute();
    } else {
      this.list.load();
    }
  }

  goToRoute() {
    m.route(app.route('notifications'));
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
