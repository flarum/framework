import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type DialogMessage from '../../common/models/DialogMessage';
export interface IMessageEditedAttrs extends ComponentAttrs {
    message: DialogMessage;
}
/** That a message was edited, and by whom and when on hover, as for posts. */
export default class MessageEdited<CustomAttrs extends IMessageEditedAttrs = IMessageEditedAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
