import Component, { type ComponentAttrs } from '../../common/Component';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
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
    linkChildren(user: User): ItemList<Mithril.Children>;
}
