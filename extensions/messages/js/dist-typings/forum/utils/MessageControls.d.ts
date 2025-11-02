import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
import type Message from '../components/Message';
declare const MessageControls: {
    controls(message: DialogMessage, context: Message<any>): ItemList<Mithril.Children>;
    sections(): {
        user: (message: DialogMessage, context: Message) => ItemList<Mithril.Children>;
        moderation: (message: DialogMessage, context: Message) => ItemList<Mithril.Children>;
        destructive: (message: DialogMessage, context: Message) => ItemList<Mithril.Children>;
    };
    userControls(message: DialogMessage, context: Message): ItemList<Mithril.Children>;
    moderationControls(message: DialogMessage, context: Message): ItemList<Mithril.Children>;
    destructiveControls(message: DialogMessage, context: Message): ItemList<Mithril.Children>;
    deleteAction(message: DialogMessage, context: Message): Promise<void> | undefined;
};
export default MessageControls;
