import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import Export from '../../common/models/Export';
import username from 'flarum/common/helpers/username';

export default class ExportAvailableNotification extends Notification {
  icon() {
    return 'fas fa-file-export';
  }

  href() {
    const exportModel = this.attrs.notification.subject() as Export;

    // Building the full url scheme so that Mithril treats this as an external link, so the download will work correctly.
    return app.forum.attribute<string>('baseUrl') + `/gdpr/export/${exportModel.file()}`;
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
}
