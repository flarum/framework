import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import classList from 'flarum/common/utils/classList';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
import describeEvent from '../utils/describeEvent';

export interface IEventMessageAttrs extends ComponentAttrs {
  message: DialogMessage;
}

/**
 * A notice in a conversation's stream, such as a member leaving it: a line
 * saying what happened and when, laid out as a discussion's event posts are.
 */
export default class EventMessage<CustomAttrs extends IEventMessageAttrs = IEventMessageAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const message = this.attrs.message;

    return (
      <div className={classList('EventMessage', `EventMessage--${message.type()}`)}>
        <Icon name={this.icon()} className="EventMessage-icon" />
        <div className="EventMessage-info">
          {describeEvent(message)} <span className="EventMessage-time">{humanTime(message.createdAt())}</span>
        </div>
      </div>
    );
  }

  icon(): string {
    switch (this.attrs.message.type()) {
      case 'userLeft':
        return 'fas fa-sign-out-alt';
      case 'renamed':
        return 'fas fa-pencil-alt';
      default:
        return 'fas fa-info-circle';
    }
  }
}
