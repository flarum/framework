import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import NotificationsDropdown from 'flarum/forum/components/NotificationsDropdown';

export default function () {
  extend(NotificationsDropdown.prototype, 'oninit', () => {
    if (app.websocket_channels.user) {
      app.websocket_channels.user.bind('notification', (data) => {
        app.session.user = app.store.pushPayload(data);

        app.notifications.clear();
        m.redraw();
      });
    }
  });
}
