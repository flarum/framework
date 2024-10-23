import ComposerBody, { IComposerBodyAttrs } from 'flarum/forum/components/ComposerBody';
import Stream from 'flarum/common/utils/Stream';
import type User from 'flarum/common/models/User';
import type Mithril from 'mithril';
import DialogMessage from '../../common/models/DialogMessage';
import type Dialog from '../../common/models/Dialog';
export interface IMessageComposerAttrs extends IComposerBodyAttrs {
    replyingTo?: Dialog;
    onsubmit?: (message: DialogMessage) => void;
    recipients?: User[];
}
/**
 * The `MessageComposer` component displays the composer content for sending
 * a new message. It adds a selection field as a header control so the user can
 * enter the recipient(s) of their message.
 */
export default class MessageComposer<CustomAttrs extends IMessageComposerAttrs = IMessageComposerAttrs> extends ComposerBody<CustomAttrs> {
    protected recipients: Stream<User[]>;
    static focusOnSelector: () => string;
    static initAttrs(attrs: IMessageComposerAttrs): void;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerItems(): import("flarum/common/utils/ItemList").default<Mithril.Children>;
    /**
     * Get the data to submit to the server when the discussion is saved.
     */
    data(): Record<string, unknown>;
    onsubmit(): void;
}
