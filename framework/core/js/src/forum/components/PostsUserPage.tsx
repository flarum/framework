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

/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
  /**
   * Whether or not the activity feed is currently loading.
   */
  loading: boolean = true;

  /**
   * Whether or not there are any more activity items that can be loaded.
   */
  moreResults: boolean = false;

  /**
   * The Post models in the feed.
   */
  posts: Post[] = [];

  /**
   * The number of activity items to load per request.
   */
  loadLimit: number = 20;

  oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>) {
    super.oninit(vnode);

    this.loadUser(m.route.param('username'));
  }

  content() {
    if (this.posts.length === 0 && !this.loading) {
      return (
        <div className="PostsUserPage">
          <Placeholder text={app.translator.trans('core.forum.user.posts_empty_text')} />
        </div>
      );
    }

    let footer;

    if (this.loading) {
      footer = <LoadingIndicator />;
    } else if (this.moreResults) {
      footer = (
        <div className="PostsUserPage-loadMore">
          <Button className="Button" onclick={this.loadMore.bind(this)}>
            {app.translator.trans('core.forum.user.posts_load_more_button')}
          </Button>
        </div>
      );
    }

    return (
      <div className="PostsUserPage">
        <ul className="PostsUserPage-list">
          {this.posts.map((post) => (
            <li>
              <div className="PostsUserPage-discussion">
                {app.translator.trans('core.forum.user.in_discussion_text', {
                  discussion: <Link href={app.route.post(post)}>{post.discussion().title()}</Link>,
                })}
              </div>

              <CommentPost post={post} />
            </li>
          ))}
        </ul>
        <div className="PostsUserPage-loadMore">{footer}</div>
      </div>
    );
  }

  /**
   * Initialize the component with a user, and trigger the loading of their
   * activity feed.
   */
  show(user: User): void {
    super.show(user);

    this.refresh();
  }

  /**
   * Clear and reload the user's activity feed.
   */
  refresh() {
    this.loading = true;
    this.posts = [];

    m.redraw();

    this.loadResults().then(this.parseResults.bind(this));
  }

  /**
   * Load a new page of the user's activity feed.
   *
   * @protected
   */
  loadResults(offset = 0) {
    return app.store.find<Post[]>('posts', {
      filter: {
        author: this.user!.username(),
        type: 'comment',
      },
      page: { offset, limit: this.loadLimit },
      sort: '-createdAt',
    });
  }

  /**
   * Load the next page of results.
   */
  loadMore() {
    this.loading = true;
    this.loadResults(this.posts.length).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the activity feed.
   */
  parseResults(results: Post[]): Post[] {
    this.loading = false;

    this.posts.push(...results);

    this.moreResults = results.length >= this.loadLimit;
    m.redraw();

    return results;
  }
}
