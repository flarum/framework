import app from 'flarum/forum/app';
import type Dialog from '../../common/models/Dialog';

/**
 * Takes the member out of a group, then off every list showing it, and off
 * their unread count if it was on it. Nothing changes until the server has
 * agreed.
 */
export default function leaveDialog(dialog: Dialog): Promise<void> {
  return app
    .request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/dialogs/' + dialog.id() + '/leave',
    })
    .then(() => {
      if (dialog.unreadCount()) {
        app.session.user!.pushAttributes({ unreadDialogCount: Math.max(0, (app.session.user!.attribute<number>('unreadDialogCount') ?? 0) - 1) });
      }

      app.dialogs.remove(dialog);
      app.dropdownDialogs.remove(dialog);

      m.redraw();
    });
}
