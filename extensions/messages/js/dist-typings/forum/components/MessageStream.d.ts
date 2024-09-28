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
}
export default class MessageStream<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
    protected replyPlaceholderComponent: Stream<any>;
    protected loadingPostComponent: Stream<any>;
    protected scrollListener: ScrollListener;
    protected initialToBottomScroll: boolean;
    protected lastTime: Date | null;
    protected checkedRead: boolean;
    protected markingAsRead: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    content(): Mithril.Children[];
    messageItem(message: DialogMessage, index: number): JSX.Element;
    timeGap(message: DialogMessage): Mithril.Children;
    onscroll(): void;
    scrollToBottom(): void;
    whileMaintainingScroll(callback: () => null | Promise<void>): void;
    markAsRead(): void;
}
