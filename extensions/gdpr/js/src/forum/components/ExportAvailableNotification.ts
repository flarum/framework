import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import Export from '../../common/models/Export';
import username from 'flarum/common/helpers/username';

export default class ExportAvailableNotification extends Notification {
  icon() {
    return 'fas fa-file-export';
  }

  exportUrl() {
    const exportModel = this.attrs.notification.subject() as Export;
    return app.forum.attribute<string>('baseUrl') + `/gdpr/export/${exportModel.file()}`;
  }

  href() {
    // Return a non-navigating href; the download is opened via window.open() in
    // markAsRead() so the mark-as-read XHR is not aborted by a page navigation.
    return '#';
  }

  content() {
    const notification = this.attrs.notification;
    return app.translator.trans('flarum-gdpr.forum.notification.export-ready', {
      username: username(notification.fromUser()),
    });
  }

  excerpt() {
    return null;
  }

  markAsRead() {
    // Open the download in a new tab so the current page is not navigated away
    // from, keeping the mark-as-read XHR alive.
    window.open(this.exportUrl(), '_blank');

    if (this.attrs.notification.isRead()) return;

    app.session.user?.pushAttributes({ unreadNotificationCount: (app.session.user.unreadNotificationCount() ?? 1) - 1 });

    this.attrs.notification.save({ isRead: true });
  }
}
