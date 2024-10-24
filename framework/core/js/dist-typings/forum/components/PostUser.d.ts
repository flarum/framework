/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
import type User from '../../common/models/User';
export interface IPostUserAttrs extends ComponentAttrs {
    /** Can be a post or similar model like private message */
    post: Post | (Model & {
        user: () => User | null | false;
    });
}
/**
 * The `PostUser` component shows the avatar and username of a post's author.
 */
export default class PostUser<CustomAttrs extends IPostUserAttrs = IPostUserAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
