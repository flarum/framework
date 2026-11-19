import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import UserSecurityPage from '../../../../src/forum/components/UserSecurityPage';
import type User from '../../../../src/common/models/User';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

describe('UserSecurityPage', () => {
  /**
   * The member's own page, with their tokens loaded.
   */
  function ownPage(): UserSecurityPage {
    const user = app.store.pushPayload<User>({ data: { type: 'users', id: '2', attributes: { username: 'member' } } } as any);
    app.session.user = user;
    app.forum.pushAttributes({ canCreateAccessToken: true });

    const page = new UserSecurityPage();
    page.user = user;
    page.state.setTokens([]);

    return page;
  }

  test('gives its sections an order that extensions can place theirs within', () => {
    const items = ownPage().settingsItems();

    expect(items.toArray().map((item: any) => item.itemName)).toEqual(['developerTokens', 'sessions', 'globalLogout']);
    expect(items.getPriority('developerTokens')).toBeGreaterThan(items.getPriority('sessions'));
    expect(items.getPriority('sessions')).toBeGreaterThan(items.getPriority('globalLogout'));

    // Something an extension puts between the sessions and logging out everywhere.
    items.add('extensionSection', 'Between', 15);

    expect(items.toArray().map((item: any) => item.itemName)).toEqual(['developerTokens', 'sessions', 'extensionSection', 'globalLogout']);
  });
});
