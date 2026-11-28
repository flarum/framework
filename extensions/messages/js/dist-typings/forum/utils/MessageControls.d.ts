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
    /**
     * As for posts: "Delete" can be undone; a deleted message can be restored,
     * or deleted for good.
     */
    destructiveControls(message: DialogMessage, context: Message): ItemList<Mithril.Children>;
    editAction(message: DialogMessage): Promise<void>;
    hideAction(message: DialogMessage): Promise<void>;
    restoreAction(message: DialogMessage): Promise<void>;
    deleteAction(message: DialogMessage, context: Message): Promise<void>;
};
export default MessageControls;
