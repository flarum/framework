import DiscussionList from './DiscussionList';
import DiscussionListState from '../states/DiscussionListState';

const UserPage = (await import(/* webpackChunkName: "forum/components/UserPage" */ './UserPage')).default;

/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);

    this.state = new DiscussionListState({
      q: 'author:' + user.username(),
      sort: 'newest',
    });

    this.state.refresh();
  }

  content() {
    return <div className="DiscussionsUserPage">{DiscussionList.component({ state: this.state })}</div>;
  }
}
