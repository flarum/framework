import app from '../../forum/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import Placeholder from '../../common/components/Placeholder';
import CommentPost from './CommentPost';
import type Post from '../../common/models/Post';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
import PostListState from '../states/PostListState';
import PostList from './PostList';

/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
  /**
   * The state of the Post models in the feed.
   */
  posts!: PostListState;

  /**
   * The number of activity items to load per request.
   */
  loadLimit: number = 20;

  oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>) {
    super.oninit(vnode);

    this.posts = new PostListState({}, this.loadLimit);

    this.loadUser(m.route.param('username'));
  }

  content() {
    return (
      <div className="PostsUserPage">
        <PostList state={this.posts} />
      </div>
    );
  }

  /**
   * Initialize the component with a user, and trigger the loading of their
   * activity feed.
   */
  show(user: User): void {
    super.show(user);

    this.posts.refreshParams(this.params(user), 1);
  }

  params(user: User) {
    return {
      filter: { author: user.username() },
    };
  }
}
