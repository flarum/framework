import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Tooltip from 'flarum/common/components/Tooltip';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
import { readStatus, type MessageReadStatus as Status } from '../utils/readReceipts';
import ReadByModal from './ReadByModal';

export interface IMessageReadStatusAttrs extends ComponentAttrs {
  message: DialogMessage;
}

export default class MessageReadStatus<CustomAttrs extends IMessageReadStatusAttrs = IMessageReadStatusAttrs> extends Component<CustomAttrs> {
  view() {
    const message = this.attrs.message;
    const dialog = message.dialog() || null;
    const status = readStatus(dialog, message);

    if (!dialog || !status) return null;

    const label = this.label(dialog, status);
    const className = classList('MessageReadStatus', `MessageReadStatus--${status.state}`);
    const icon = <Icon name={status.state === 'sent' ? 'fas fa-check' : 'fas fa-check-double'} />;

    if (dialog.isGroup() && status.readCount) {
      return (
        <Tooltip text={label}>
          <button
            type="button"
            className={classList(className, 'Button Button--text')}
            aria-label={label}
            onclick={() => app.modal.show(ReadByModal, { dialog, message })}
          >
            {icon}
          </button>
        </Tooltip>
      );
    }

    return (
      <Tooltip text={label}>
        <span className={className} role="img" aria-label={label}>
          {icon}
        </span>
      </Tooltip>
    );
  }

  label(dialog: Dialog, status: Status): string {
    const prefix = 'flarum-messages.forum.message.read_status.';

    if (status.state === 'sent') return app.translator.trans(prefix + 'sent_tooltip', {}, true);
    if (!dialog.isGroup()) return app.translator.trans(prefix + 'read_tooltip', {}, true);
    if (status.state === 'all') return app.translator.trans(prefix + 'read_by_everyone_tooltip', {}, true);

    return app.translator.trans(prefix + 'read_by_some_tooltip', { count: status.readCount, total: status.members ?? 0 }, true);
  }
}
