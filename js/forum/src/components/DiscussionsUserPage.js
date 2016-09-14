import UserPage from 'flarum/components/UserPage';
import UserDiscussionList from 'flarum/components/UserDiscussionList';

/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage {
  init() {
    super.init();

    this.loadUser(m.route.param('username'));
  }

  content() {
    return (
      <div className="DiscussionsUserPage">
        {UserDiscussionList.component({
          params: {
            q: 'author:' + this.user.username()
          },
          user: this.user
        })}
      </div>
    );
  }
}
