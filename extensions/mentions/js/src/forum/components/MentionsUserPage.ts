import PostsUserPage from 'flarum/forum/components/PostsUserPage';
import type User from 'flarum/common/models/User';

/**
 * The `MentionsUserPage` component shows post which user Mentioned at
 */
export default class MentionsUserPage extends PostsUserPage {
  params(user: User) {
    return {
      filter: {
        type: 'comment',
        mentioned: user.id(),
      },
    };
  }
}
