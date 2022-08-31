import app from '../../forum/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import NotificationList from './NotificationList';
import type Mithril from 'mithril';
import extractText from '../../common/utils/extractText';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.history.push('notifications', extractText(app.translator.trans('core.forum.notifications.title')));

    app.notifications.load();

    this.bodyClass = 'App--notifications';
  }

  view() {
    return (
      <div className="NotificationsPage">
        <NotificationList state={app.notifications}></NotificationList>
      </div>
    );
  }
}
