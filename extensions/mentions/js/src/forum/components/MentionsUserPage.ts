import app from 'flarum/forum/app';
import PostsUserPage from 'flarum/forum/components/PostsUserPage';
import type User from 'flarum/common/models/User';

/**
 * The `MentionsUserPage` component shows post which user Mentioned at
 */
export default class MentionsUserPage extends PostsUserPage {
  show(user: User): void {
    super.show(user);

    // A post save reloads this list (see the save hook in the forum entry
    // point), so that it keeps matching the count beside the tab.
    app.current.set('mentions', this.posts);
  }

  params(user: User): ReturnType<PostsUserPage['params']> {
    return {
      filter: {
        type: 'comment',
        mentioned: user.id(),
      },
    } as unknown as ReturnType<PostsUserPage['params']>;
  }
}
