import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import MessageStreamState from '../states/MessageStreamState';
import DialogMessage from '../../common/models/DialogMessage';
import Stream from 'flarum/common/utils/Stream';
import ScrollListener from 'flarum/common/utils/ScrollListener';
import Dialog from '../../common/models/Dialog';
export interface IDialogStreamAttrs extends ComponentAttrs {
    dialog: Dialog;
    state: MessageStreamState;
    /** The number of the message to open on, from a permalink. */
    near?: number | null;
}
export default class MessageStream<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
    protected replyPlaceholderComponent: Stream<any>;
    protected loadingPostComponent: Stream<any>;
    protected scrollListener: ScrollListener;
    protected initialScrollDone: boolean;
    protected lastTime: Date | null;
    protected markingAsRead: boolean;
    protected scrollSettleTimer: number | null;
    protected onVisibilityChange: () => void;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /** Once the first messages are on screen: to the permalinked message, or the end. */
    protected settle(): void;
    view(): JSX.Element;
    content(): Mithril.Children[];
    messageItem(message: DialogMessage): JSX.Element;
    timeGap(message: DialogMessage): Mithril.Children;
    /**
     * A message that has just joined the conversation, from this member or
     * another. The reader is taken along when they were at the end already, or
     * sent it themselves; otherwise they keep their place.
     */
    receive(message: DialogMessage): void;
    /** After the redraw a state change has asked for. */
    afterRedraw(callback: () => void): void;
    onscroll(): void;
    isAtBottom(): boolean;
    scrollToBottom(): void;
    /** The permalinked message, when there is one on screen; otherwise the end. */
    protected scrollToStart(): void;
    whileMaintainingScroll(callback: () => null | Promise<void>): void;
    /**
     * Marks read up to the last message on screen. Only what the reader can
     * see counts: not a tab in the background, and not a pane the phone layout
     * keeps off screen.
     */
    markAsRead(): void;
    protected lastVisibleMessageId(): number;
}
