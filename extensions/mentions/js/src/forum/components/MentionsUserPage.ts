import app from 'flarum/forum/app';
import PostsUserPage from 'flarum/forum/components/PostsUserPage';
import type PostListState from 'flarum/forum/states/PostListState';
import type { PostListParams } from 'flarum/forum/states/PostListState';
import type User from 'flarum/common/models/User';

/**
 * What the Mentions tab records on the current page: the list it shows, and the
 * user whose mentions that list holds.
 */
export interface IMentionsPageState {
  user: User;
  posts: PostListState;
}

/**
 * The `MentionsUserPage` component shows post which user Mentioned at
 */
export default class MentionsUserPage extends PostsUserPage {
  show(user: User): void {
    super.show(user);

    // A post save reloads this list when it changes this user's mention count
    // (see the save hook in the forum entry point), so the page records whose
    // list it is showing alongside the list.
    app.current.set('mentions', { user, posts: this.posts });
  }

  params(user: User): PostListParams {
    return {
      filter: {
        type: 'comment',
        mentioned: user.id(),
      },
    };
  }
}
