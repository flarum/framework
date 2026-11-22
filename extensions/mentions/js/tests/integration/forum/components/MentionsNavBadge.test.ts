import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import SelectDropdown from 'flarum/common/components/SelectDropdown';
import UserPage from 'flarum/forum/components/UserPage';
import type User from 'flarum/common/models/User';

import '../../../../src/forum';
import extenders from '../../../../src/forum/extend';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.bootExtensions({ 'flarum-mentions': { extend: extenders } });
});

/**
 * The user page's navigation, as `UserPage.sidebarItems` renders it.
 */
function openProfileNav(user: User) {
  return mq({
    view: () =>
      m(SelectDropdown, { className: 'App-titleControl', buttonClassName: 'Button' }, (UserPage.prototype.navItems as any).call({ user }).toArray()),
  });
}

const menuBadge = (rendered: any) => rendered.rootEl.querySelector('.item-mentions .Button-badge')?.textContent;

describe('a Mentions tab badge on an open profile', () => {
  it('follows the user record when a saved post changes the count', () => {
    const user = app.store.pushPayload({
      data: makeUser({ id: '2', attributes: { username: 'alice', slug: 'alice', mentionCount: 2 } }),
    }) as User;

    const rendered = openProfileNav(user);

    expect(menuBadge(rendered)).toBe('2');

    // What a post save's response does to the store.
    user.pushAttributes({ mentionCount: 1 });

    rendered.redraw();

    expect(menuBadge(rendered)).toBe('1');
  });
});
