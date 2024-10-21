import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
import AbstractPost, { type IAbstractPostAttrs } from 'flarum/forum/components/AbstractPost';
import type User from 'flarum/common/models/User';
import DialogMessage from '../../common/models/DialogMessage';
export interface IMessageAttrs extends IAbstractPostAttrs {
    message: DialogMessage;
}
/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Message<CustomAttrs extends IMessageAttrs = IMessageAttrs> extends AbstractPost<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    user(): User | null | false;
    controls(): Mithril.Children[];
    freshness(): Date;
    createdByStarter(): boolean;
    onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): boolean;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    elementAttrs(): Record<string, unknown>;
    header(): Mithril.Children;
    content(): Mithril.Children[];
    classes(existing?: string): string[];
    actionItems(): ItemList<Mithril.Children>;
    footerItems(): ItemList<Mithril.Children>;
    sideItems(): ItemList<Mithril.Children>;
    avatar(): Mithril.Children;
    headerItems(): ItemList<Mithril.Children>;
}
