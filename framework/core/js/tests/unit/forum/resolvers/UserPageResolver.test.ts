import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import PageState from '../../../../src/common/states/PageState';
import UserPage from '../../../../src/forum/components/UserPage';
import UserPageResolver from '../../../../src/forum/resolvers/UserPageResolver';
import { makeUser } from '../../../factory';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

function pushUser(id: string, username: string, slug: string = username) {
  return app.store.pushObject<any>(makeUser({ id, attributes: { username, slug, displayName: username, joinTime: '2020-05-21T11:51:06+00:00' } }));
}

function render(address: string, slug: string, routeName = 'user.posts') {
  window.history.replaceState({ scroll: 1 }, '', address);

  // @ts-expect-error only `param()` is stubbed
  m.route = { param: (key?: string) => (key ? ({ username: slug } as Record<string, string>)[key] : { username: slug }) };

  new UserPageResolver(UserPage, routeName).render({ attrs: {} } as any);

  return window.location.pathname + window.location.search + window.location.hash;
}

function resolved(user: any, slug: string, routeName = 'user.posts') {
  app.current = new PageState(UserPage, { routeName, user, resolvedUser: { slug, user } });
}

describe('UserPageResolver', () => {
  beforeEach(() => {
    app.current = new PageState(UserPage, { routeName: 'user.posts' });
  });

  // Under the username driver, a username may start with another user's ID.
  test('leaves the address of a user whose username starts with another user’s ID', () => {
    pushUser('7', 'seven');
    const fan = pushUser('70', '7-fan');

    expect(render('/u/7-fan', '7-fan')).toBe('/u/7-fan');

    resolved(fan, '7-fan');

    expect(render('/u/7-fan', '7-fan')).toBe('/u/7-fan');
  });

  test('leaves the address of a user whose username is another user’s ID', () => {
    pushUser('123', 'someone');

    expect(render('/u/123', '123')).toBe('/u/123');
  });

  test('corrects the address to the slug of the user it was resolved to', () => {
    const alice = pushUser('2', 'alice', '2-alice');

    resolved(alice, '2');

    expect(render('/u/2?sort=newest#top', '2')).toBe('/u/2-alice?sort=newest#top');
    expect(window.history.state).toEqual({ scroll: 1 });
  });

  test('follows a rename of the user shown', () => {
    const fay = pushUser('10', 'fay', '10-fay');

    resolved(fay, '10-fay');
    fay.pushAttributes({ slug: '10-faye' });

    expect(render('/u/10-fay', '10-fay')).toBe('/u/10-faye');
  });

  test('corrects the address of other user routes', () => {
    const bob = pushUser('3', 'bob', '3-bob');

    resolved(bob, '3', 'user.security');

    expect(render('/u/3/security', '3', 'user.security')).toBe('/u/3-bob/security');
  });

  test('leaves the address while the user has not been resolved', () => {
    pushUser('4', 'carol', '4-carol');

    expect(render('/u/4', '4')).toBe('/u/4');
  });

  test('leaves the address when the user was resolved for another address', () => {
    const one = pushUser('5', 'test-one');
    pushUser('6', 'test-two');

    resolved(one, 'test-one');

    expect(render('/u/test-two', 'test-two')).toBe('/u/test-two');
  });

  test('leaves the address when the page state belongs to another route', () => {
    const dave = pushUser('8', 'dave', '8-dave');

    resolved(dave, '8', 'user.discussions');

    expect(render('/u/8', '8')).toBe('/u/8');
  });

  // Mithril changes the address at once, but resolves the new route later.
  test('leaves the address while navigating away', () => {
    const erin = pushUser('9', 'erin', '9-erin');

    resolved(erin, '9');

    expect(render('/d/1', '9')).toBe('/d/1');
  });
});
