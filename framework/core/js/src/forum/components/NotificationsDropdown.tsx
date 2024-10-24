import app from '../../forum/app';
import NotificationList from './NotificationList';
import extractText from '../../common/utils/extractText';
import HeaderDropdown, { IHeaderDropdownAttrs } from './HeaderDropdown';
import classList from '../../common/utils/classList';

export interface INotificationsDropdown extends IHeaderDropdownAttrs {}

export default class NotificationsDropdown<CustomAttrs extends INotificationsDropdown = INotificationsDropdown> extends HeaderDropdown<CustomAttrs> {
  static initAttrs(attrs: INotificationsDropdown) {
    attrs.className = classList('NotificationsDropdown', attrs.className);
    attrs.icon ||= 'fas fa-bell';

    // For best a11y support, both `title` and `aria-label` should be used
    attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.forum.notifications.toggle_dropdown_accessible_label'));

    super.initAttrs(attrs);
  }

  getContent() {
    return <NotificationList state={this.attrs.state} />;
  }

  goToRoute() {
    m.route.set(app.route('notifications'));
  }

  getUnreadCount() {
    return app.session.user!.unreadNotificationCount()!;
  }

  getNewCount() {
    return app.session.user!.newNotificationCount()!;
  }
}
