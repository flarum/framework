import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type User from 'flarum/common/models/User';
import AuditBrowser from '../../common/components/AuditBrowser';

export interface IUserAuditModalAttrs extends IInternalModalAttrs {
  user: User;
}

export default class UserAuditModal<CustomAttrs extends IUserAuditModalAttrs = IUserAuditModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'AuditModal UserAuditModal';
  }

  title() {
    return app.translator.trans('flarum-audit.forum.modal.user-title');
  }

  content() {
    return <AuditBrowser baseQ={'user:' + this.attrs.user.username()} />;
  }
}
