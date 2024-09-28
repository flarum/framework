/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import type Post from '../../common/models/Post';
export interface IPostTypeAttrs extends ComponentAttrs {
    post: Post;
}
export default class PostType<CustomAttrs extends IPostTypeAttrs = IPostTypeAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
