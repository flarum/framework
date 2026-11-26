import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import type ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
export interface ICommentAttrs extends ComponentAttrs {
    headerItems: ItemList<Mithril.Children>;
    user: User | false | undefined;
    cardVisible: boolean;
    isEditing: boolean;
    isHidden: boolean;
    contentHtml: string;
    search?: string;
    /** The post the HTML belongs to, or a similar model like a private message. */
    post?: Post | Model;
}
export default class Comment<CustomAttrs extends ICommentAttrs = ICommentAttrs> extends Component<CustomAttrs> {
    /**
     * The HTML string last handed to Mithril, or null while editing. Mithril
     * re-inserts the HTML whenever it changes, inert scripts included.
     */
    renderedHtml: string | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    /**
     * The post HTML to render, before search highlighting. Override it to
     * transform the markup: `extend` can't change a string return value.
     *
     * Not called while the post is being edited. The result is rendered as
     * trusted HTML. Posts and messages rebuild it when their own state changes,
     * not on every redraw, so return the same HTML for the same input and cache
     * costly work on it.
     */
    contentHtml(): string;
    view(): JSX.Element[];
}
