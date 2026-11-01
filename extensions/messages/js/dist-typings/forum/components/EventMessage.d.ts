import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
export interface IEventMessageAttrs extends ComponentAttrs {
    message: DialogMessage;
}
/**
 * A notice in a conversation's stream, such as a member leaving it: a line
 * saying what happened and when, laid out as a discussion's event posts are.
 */
export default class EventMessage<CustomAttrs extends IEventMessageAttrs = IEventMessageAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
    icon(): string;
}
