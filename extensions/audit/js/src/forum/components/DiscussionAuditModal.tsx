import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
import AuditBrowser from '../../common/components/AuditBrowser';

export interface IDiscussionAuditModalAttrs extends IInternalModalAttrs {
  discussion: Discussion;
}

export default class DiscussionAuditModal<CustomAttrs extends IDiscussionAuditModalAttrs = IDiscussionAuditModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'AuditModal DiscussionAuditModal';
  }

  title() {
    return app.translator.trans('flarum-audit.forum.modal.discussion-title');
  }

  content() {
    return <AuditBrowser baseQ={'discussion:' + this.attrs.discussion.id()} />;
  }
}
