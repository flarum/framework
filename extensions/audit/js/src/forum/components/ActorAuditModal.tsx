import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type User from 'flarum/common/models/User';
import AuditBrowser from '../../common/components/AuditBrowser';

export interface IActorAuditModalAttrs extends IInternalModalAttrs {
  user: User;
}

export default class ActorAuditModal<CustomAttrs extends IActorAuditModalAttrs = IActorAuditModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'AuditModal ActorAuditModal';
  }

  title() {
    return app.translator.trans('flarum-audit.forum.modal.actor-title');
  }

  content() {
    return <AuditBrowser baseQ={'actor:' + this.attrs.user.username()} />;
  }
}
