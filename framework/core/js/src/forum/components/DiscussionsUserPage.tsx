import UserPage, { IUserPageAttrs } from './UserPage';
import DiscussionList from './DiscussionList';
import DiscussionListState from '../states/DiscussionListState';
import type Mithril from 'mithril';
import type User from '../../common/models/User';

/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage<IUserPageAttrs, DiscussionListState> {
  oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>) {
    super.oninit(vnode);

    this.loadUser(m.route.param('username'));
  }

  show(user: User): void {
    super.show(user);

    this.state = new DiscussionListState({
      filter: { author: user.username() },
      sort: 'newest',
    });

    this.state.refresh();
  }

  content() {
    return (
      <div className="DiscussionsUserPage">
        <DiscussionList state={this.state} />
      </div>
    );
  }
}
