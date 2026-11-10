import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import AuditBrowser from '../../common/components/AuditBrowser';

export default class AuditModal extends Modal {
  className() {
    return 'AuditModal';
  }

  title() {
    return app.translator.trans('flarum-audit.forum.modal.all-title');
  }

  content() {
    return <AuditBrowser />;
  }
}
