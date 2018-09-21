import UserPage from './UserPage';
import DiscussionList from './DiscussionList';

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
        {DiscussionList.component({
          params: {
            q: 'author:' + this.user.username(),
            sort: 'newest'
          }
        })}
      </div>
    );
  }
}
