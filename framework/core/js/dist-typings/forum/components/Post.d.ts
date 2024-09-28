import ItemList from '../../common/utils/ItemList';
import type PostModel from '../../common/models/Post';
import Mithril from 'mithril';
import AbstractPost, { type IAbstractPostAttrs } from './AbstractPost';
import type User from '../../common/models/User';
export interface IPostAttrs extends IAbstractPostAttrs {
    post: PostModel;
}
/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Post<CustomAttrs extends IPostAttrs = IPostAttrs> extends AbstractPost<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    user(): User | null | false;
    controls(): Mithril.Children[];
    freshness(): Date;
    createdByStarter(): boolean;
    onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): boolean;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Get attributes for the post element.
     */
    elementAttrs(): Record<string, unknown>;
    header(): Mithril.Children;
    /**
     * Get the post's content.
     */
    content(): Mithril.Children[];
    /**
     * Get the post's classes.
     */
    classes(existing?: string): string[];
    /**
     * Build an item list for the post's actions.
     */
    actionItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the post's footer.
     */
    footerItems(): ItemList<Mithril.Children>;
    sideItems(): ItemList<Mithril.Children>;
    avatar(): Mithril.Children;
}
