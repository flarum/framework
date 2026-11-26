import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
import { type MessageReadStatus as Status } from '../utils/readReceipts';
export interface IMessageReadStatusAttrs extends ComponentAttrs {
    message: DialogMessage;
}
export default class MessageReadStatus<CustomAttrs extends IMessageReadStatusAttrs = IMessageReadStatusAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element | null;
    label(dialog: Dialog, status: Status): string;
}
