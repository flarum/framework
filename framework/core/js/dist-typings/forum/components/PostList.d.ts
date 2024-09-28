/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import PostListState from '../states/PostListState';
export interface IPostListAttrs extends ComponentAttrs {
    state: PostListState;
}
export default class PostList<CustomAttrs extends IPostListAttrs = IPostListAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
