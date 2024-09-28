import Component, { type ComponentAttrs } from '../../common/Component';
import type Post from '../../common/models/Post';
import Mithril from 'mithril';
import { PostListParams } from '../states/PostListState';
export interface IPostListItemAttrs extends ComponentAttrs {
    post: Post;
    params: PostListParams;
}
export default class PostListItem<CustomAttrs extends IPostListItemAttrs = IPostListItemAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
}
