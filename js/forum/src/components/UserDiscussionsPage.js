import UserPage from 'flarum/components/UserPage';
import DiscussionList from 'flarum/components/DiscussionList';

/**
 * The `UserDiscussionsPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class UserDiscussionsPage extends UserPage {
  constructor(...args) {
    super(...args);

    this.loadUser(m.route.param('username'));
  }

  content() {
    return (
      <div className="UserPostsPage">
        {DiscussionList.component({
          params: {
            q: 'author:' + this.user.username()
          }
        })}
      </div>
    );
  }
}
