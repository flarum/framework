import Page from '../../common/components/Page';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    app.history.push('notifications');

    import(/* webpackChunkName: "forum/components/NotificationList" */ './NotificationList').then((NotificationList) => {
      this.NotificationList = NotificationList.default;
      m.redraw();
    });

    app.notifications.load();

    this.bodyClass = 'App--notifications';
  }

  view() {
    const NotificationList = this.NotificationList;

    return <div className="NotificationsPage">{NotificationList ? <NotificationList state={app.notifications} /> : ''}</div>;
  }
}
