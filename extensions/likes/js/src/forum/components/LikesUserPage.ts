import PostsUserPage from 'flarum/forum/components/PostsUserPage';
import type User from 'flarum/common/models/User';

/**
 * The `LikesUserPage` component shows posts which user the user liked.
 */
export default class LikesUserPage extends PostsUserPage {
  params(user: User) {
    return {
      filter: {
        type: 'comment',
        likedBy: user.id(),
      },
    };
  }
}
