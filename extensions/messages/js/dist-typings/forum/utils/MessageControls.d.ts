import type DialogMessage from '../../common/models/DialogMessage';
import type Message from '../components/Message';
declare const MessageControls: {
    controls(message: DialogMessage, context: Message<any>): any;
    sections(): {
        user: (message: DialogMessage, context: Message) => any;
        moderation: (message: DialogMessage, context: Message) => any;
        destructive: (message: DialogMessage, context: Message) => any;
    };
    userControls(message: DialogMessage, context: Message): any;
    moderationControls(message: DialogMessage, context: Message): any;
    destructiveControls(message: DialogMessage, context: Message): any;
    deleteAction(message: DialogMessage, context: Message): any;
};
export default MessageControls;
