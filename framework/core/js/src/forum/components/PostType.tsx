import Component, { type ComponentAttrs } from '../../common/Component';
import type Post from '../../common/models/Post';
import app from '../app';

export interface IPostTypeAttrs extends ComponentAttrs {
  post: Post;
}

export default class PostType<CustomAttrs extends IPostTypeAttrs = IPostTypeAttrs> extends Component<CustomAttrs> {
  view() {
    const post = this.attrs.post;
    const PostComponent = app.postComponents[post.contentType()!];

    return !!PostComponent && <PostComponent post={post} />;
  }
}
