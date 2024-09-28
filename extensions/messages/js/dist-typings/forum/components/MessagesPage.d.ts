import Page, { IPageAttrs } from 'flarum/common/components/Page';
import Mithril from 'mithril';
import Dialog from '../../common/models/Dialog';
import Stream from 'flarum/common/utils/Stream';
import ItemList from 'flarum/common/utils/ItemList';
export interface IMessagesPageAttrs extends IPageAttrs {
}
export default class MessagesPage<CustomAttrs extends IMessagesPageAttrs = IMessagesPageAttrs> extends Page<CustomAttrs> {
    protected selectedDialog: Stream<Dialog | null>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    dialogRequestParams(): {
        include: string;
    };
    protected initDialog(): Promise<void>;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    hero(): Mithril.Children;
    /**
     * Build an item list for the part of the toolbar which is concerned with how
     * the results are displayed. By default this is just a select box to change
     * the way discussions are sorted.
     */
    viewItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the part of the toolbar which is about taking action
     * on the results. By default this is just a "mark all as read" button.
     */
    actionItems(): ItemList<Mithril.Children>;
}
