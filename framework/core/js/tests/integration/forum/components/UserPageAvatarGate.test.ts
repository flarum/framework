import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import { app } from '../../../../src/forum';
import UserPage from '../../../../src/forum/components/UserPage';
import User from '../../../../src/common/models/User';
import { makeUser } from '../../../factory';

beforeAll(() => bootstrapForum());

/**
 * `UserPage` is abstract — it expects a subclass to supply the user and the content
 * area. This mounts the real page (and therefore the real `editable` gate) with a
 * user we control.
 */
function mountFor(user: User) {
  class TestUserPage extends UserPage {
    oninit(vnode: any) {
      super.oninit(vnode);
      this.user = user;
    }

    content() {
      return null;
    }

    // The real sidebar mounts AffixedSidebar, whose oncreate measures elements with
    // jQuery and cannot run under jsdom. It plays no part in the avatar gate.
    sidebar() {
      return null;
    }
  }

  return mq(TestUserPage, {});
}

function userWith(attributes: Record<string, unknown>): User {
  return new User(makeUser({ id: '5', attributes }));
}

describe('UserPage avatar gate', () => {
  beforeAll(() => app.boot());

  it('renders the avatar editor when the user may edit their own avatar', () => {
    const page = mountFor(userWith({ canEditAvatar: true, canEdit: false }));

    expect(page).toHaveElement('.AvatarEditor');
  });

  it('does not render the avatar editor when the user may not', () => {
    const page = mountFor(userWith({ canEditAvatar: false, canEdit: false }));

    expect(page).not.toHaveElement('.AvatarEditor');
    // The avatar itself is still shown, just not as an editable control.
    expect(page).toHaveElement('.UserCard-avatar');
  });

  it('renders the avatar editor for a moderator who may edit the user', () => {
    const page = mountFor(userWith({ canEditAvatar: false, canEdit: true }));

    expect(page).toHaveElement('.AvatarEditor');
  });
});
