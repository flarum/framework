import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import HeaderDropdown from 'flarum/forum/components/HeaderDropdown';

export default function () {
  extend(HeaderDropdown.prototype, 'oncreate', () => {
    if (app.websocket_channels.user) {
      app.websocket_channels.user.bind('flagged', (data) => {
        app.session.user = app.store.pushPayload(data);

        app.forum.pushAttributes({ flagCount: app.session.user.attribute('newFlagCount') });
        app.flags.cache = null;

        m.redraw();
      });
    }
  });
}
