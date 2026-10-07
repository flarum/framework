import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Dialog from '../../common/models/Dialog';
import type Mithril from 'mithril';
import MessageStreamState from '../states/MessageStreamState';
import ItemList from 'flarum/common/utils/ItemList';
export interface IDialogStreamAttrs extends ComponentAttrs {
    dialog: Dialog;
    onback?: () => void;
}
export default class DialogSection<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
    protected loading: boolean;
    protected messages: MessageStreamState;
    /**
     * The permalinked message, taken from the route once. The stream drops it
     * from the address after opening on it, and the route's own copy would
     * still say it afterwards.
     */
    protected near: number | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    requestParams(): any;
    view(): JSX.Element;
    /**
     * Whether to say the other member can't reply: they can't send messages, and
     * the viewer can't send here either. Not to viewers who can't send at all,
     * who aren't told whether anyone else can.
     */
    recipientCannotReply(): boolean;
    actionItems(): ItemList<Mithril.Children>;
    controlItems(): ItemList<Mithril.Children>;
}
