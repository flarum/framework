import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import m from 'mithril';
import UserPage from 'flarum/forum/components/UserPage';
import type User from 'flarum/common/models/User';

import '../../../src/forum';
import extenders from '../../../src/forum/extend';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.bootExtensions({ 'flarum-mentions': { extend: extenders } });
});

describe('the mentions tab on a profile', () => {
  it('renders no badge when the API did not send a count', () => {
    app.store.pushPayload({
      data: { type: 'users', id: '81', attributes: { username: 'someone' } },
    } as any);
    const user = app.store.getById<User>('users', '81')!;

    const root = document.createElement('div');
    document.body.append(root);
    const profile = new UserPage();
    profile.user = user;
    m.mount(root, { view: () => profile.navItems().get('mentions') });

    try {
      expect(root.querySelector('.Button-badge')).toBeNull();
      expect(root.textContent).not.toContain('undefined');
    } finally {
      m.mount(root, null);
      root.remove();
    }
  });
});
