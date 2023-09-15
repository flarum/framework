import app from '../../forum/app';
import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import icon from '../../common/helpers/icon';
import classList from '../../common/utils/classList';
import NotificationList from './NotificationList';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';

export interface INotificationsDropdown extends IDropdownAttrs {}

export default class NotificationsDropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: INotificationsDropdown) {
    attrs.className ||= 'NotificationsDropdown';
    attrs.buttonClassName ||= 'Button Button--flat';
    attrs.menuClassName ||= 'Dropdown-menu--right';
    attrs.label ||= extractText(app.translator.trans('core.forum.notifications.tooltip'));
    attrs.icon ||= 'fas fa-bell';

    // For best a11y support, both `title` and `aria-label` should be used
    attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.forum.notifications.toggle_dropdown_accessible_label'));

    super.initAttrs(attrs);
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    const newNotifications = this.getNewCount();

    const vdom = super.getButton(children);

    vdom.attrs.title = this.attrs.label;

    vdom.attrs.className = classList(vdom.attrs.className, [newNotifications && 'new']);
    vdom.attrs.onclick = this.onclick.bind(this);

    return vdom;
  }

  getButtonContent(): Mithril.ChildArray {
    const unread = this.getUnreadCount();

    return [
      this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : null,
      unread !== 0 && <span className="NotificationsDropdown-unread">{unread}</span>,
      <span className="Button-label">{this.attrs.label}</span>,
    ];
  }

  getMenu() {
    return (
      <div className={classList('Dropdown-menu', this.attrs.menuClassName)} onclick={this.menuClick.bind(this)}>
        {this.showing && <NotificationList state={this.attrs.state} />}
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
    return app.session.user!.unreadNotificationCount();
  }

  getNewCount() {
    return app.session.user!.newNotificationCount();
  }

  menuClick(e: MouseEvent) {
    // Don't close the notifications dropdown if the user is opening a link in a
    // new tab or window.
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.button === 1) e.stopPropagation();
  }
}
