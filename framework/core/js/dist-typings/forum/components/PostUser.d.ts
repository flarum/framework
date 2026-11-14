import Component, { type ComponentAttrs } from '../../common/Component';
import Post from '../../common/models/Post';
import type Model from '../../common/Model';
import type User from '../../common/models/User';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface IPostUserAttrs extends ComponentAttrs {
    /** Can be a post or similar model like private message */
    post: Post | (Model & {
        user: () => User | null | false;
    });
    /**
     * The heading level of the author's name. Defaults to 2, which follows the
     * discussion title. Pass 3 where the author sits below a heading of its own,
     * such as a conversation title.
     */
    headingLevel?: 2 | 3;
}
/**
 * The `PostUser` component shows the avatar and username of a post's author.
 */
export default class PostUser<CustomAttrs extends IPostUserAttrs = IPostUserAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    protected headingTag(): 'h2' | 'h3';
    noUserViewItems(user: false | User | null, post: Post | (Model & {
        user: () => false | User | null;
    })): ItemList<Mithril.Children>;
    userViewItems(user: User, post: Post | (Model & {
        user: () => false | User | null;
    })): ItemList<Mithril.Children>;
    /**
     * The "via …" byline for a post made outside the forum's own interface, or
     * null for one made in it.
     */
    sourceView(post: IPostUserAttrs['post']): Mithril.Children;
    /**
     * What follows "via": the name stored with the post, else the source's own
     * label. Extensions can label their own sources here.
     */
    sourceLabel(post: Post): string | null;
    /**
     * More about the source, shown on hover. Extensions can describe their own
     * sources here.
     */
    sourceTooltip(post: Post): string | null;
    linkChildren(user: User): ItemList<Mithril.Children>;
}
