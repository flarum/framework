import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Tooltip from 'flarum/common/components/Tooltip';
import humanTime from 'flarum/common/utils/humanTime';
import type DialogMessage from '../../common/models/DialogMessage';

export interface IMessageEditedAttrs extends ComponentAttrs {
  message: DialogMessage;
}

/** That a message was edited, and by whom and when on hover, as for posts. */
export default class MessageEdited<CustomAttrs extends IMessageEditedAttrs = IMessageEditedAttrs> extends Component<CustomAttrs> {
  view() {
    const message = this.attrs.message;
    const info = app.translator.trans('flarum-messages.forum.message.edited_tooltip', {
      user: message.editedUser() || null,
      ago: humanTime(message.editedAt()!),
    });

    return (
      <Tooltip text={info}>
        <span className="PostEdited">{app.translator.trans('flarum-messages.forum.message.edited_text')}</span>
      </Tooltip>
    );
  }
}
