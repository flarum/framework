import Component, { type ComponentAttrs } from '../../common/Component';
import type Post from '../../common/models/Post';
import Mithril from 'mithril';
import app from '../app';
import Link from '../../common/components/Link';
import CommentPost from './CommentPost';
import { PostListParams } from '../states/PostListState';

export interface IPostListItemAttrs extends ComponentAttrs {
  post: Post;
  params: PostListParams;
}

export default class PostListItem<CustomAttrs extends IPostListItemAttrs = IPostListItemAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const post = this.attrs.post;

    return (
      <div className="PostListItem">
        <div className="PostListItem-discussion">
          {app.translator.trans('core.forum.post_list.in_discussion_text', {
            discussion: <Link href={app.route.post(post)}>{post.discussion().title()}</Link>,
          })}
        </div>

        <CommentPost post={post} params={this.attrs.params} />
      </div>
    );
  }
}
