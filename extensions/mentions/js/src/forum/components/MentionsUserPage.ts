import app from 'flarum/forum/app';
import PostsUserPage from 'flarum/forum/components/PostsUserPage';
import type PageState from 'flarum/common/states/PageState';
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
  /**
   * The page state this component loaded its user on. `UserPage.loadUser` reads
   * a profile in the background, so its response can arrive after the reader
   * has moved on; this is how `show()` tells whether the reader is still here.
   */
  pageState?: PageState;

  loadUser(username: string): Promise<void> {
    // Record the page state before asking for the user: the preloaded and
    // stored branches of `UserPage.loadUser` call `show()` synchronously, so
    // capturing it afterwards would be too late.
    this.pageState = app.current;

    return super.loadUser(username);
  }

  show(user: User): void {
    // Two checks, because a page can be left in two ways. The state identity
    // catches a reader who moved to a page that has not resolved a user yet (a
    // different profile still loading, or a page that is not a profile); the
    // recorded user catches a page state that has since resolved another
    // profile. Either way, this response belongs to a page the reader has left:
    // it must not take over the page or the list the save hook tracks.
    if (this.pageState && app.current !== this.pageState) {
      return;
    }

    const resolved = app.current.get('resolvedUser');

    if (resolved && resolved.user.id() !== user.id()) {
      return;
    }

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
