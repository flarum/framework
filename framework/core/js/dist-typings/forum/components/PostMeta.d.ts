/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import Post from '../../common/models/Post';
import type Model from '../../common/Model';
import type User from '../../common/models/User';
type ModelType = Post | (Model & {
    user: () => User | null | false;
    createdAt: () => Date;
});
export interface IPostMetaAttrs extends ComponentAttrs {
    /** Can be a post or similar model like private message */
    post: ModelType;
    permalink?: () => string;
}
/**
 * The `PostMeta` component displays the time of a post, and when clicked, shows
 * a dropdown containing more information about the post (number, full time,
 * permalink).
 */
export default class PostMeta<CustomAttrs extends IPostMetaAttrs = IPostMetaAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    /**
     * Get the permalink for the given post.
     */
    getPermalink(post: ModelType): null | string;
    postIdentifier(post: ModelType): string | null;
}
export {};
