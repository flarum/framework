import Component, { ComponentAttrs } from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import ItemList from '../../common/utils/ItemList';
import type PostModel from '../../common/models/Post';
import type Mithril from 'mithril';
export interface IPostAttrs extends ComponentAttrs {
    post: PostModel;
}
/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Post<CustomAttrs extends IPostAttrs = IPostAttrs> extends Component<CustomAttrs> {
    /**
     * May be set by subclasses.
     */
    loading: boolean;
    /**
     * Ensures that the post will not be redrawn
     * unless new data comes in.
     */
    subtree: SubtreeRetainer;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): boolean;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Get attributes for the post element.
     */
    elementAttrs(): Record<string, unknown>;
    /**
     * Get the post's content.
     */
    content(): Mithril.Children;
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
}
