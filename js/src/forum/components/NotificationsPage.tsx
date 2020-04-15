import app from '../app';

import Page from './Page';
import NotificationList from './NotificationList';

/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage extends Page {
    list = (<NotificationList />);

    oninit(vnode) {
        super.oninit(vnode);

        app.history.push('notifications');

        this.bodyClass = 'App--notifications';
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        m.redraw();
    }

    view() {
        return <div className="NotificationsPage">{this.list}</div>;
    }
}
