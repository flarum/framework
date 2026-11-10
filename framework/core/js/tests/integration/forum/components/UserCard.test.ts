import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/forum';
import UserCard from '../../../../src/forum/components/UserCard';
import User from '../../../../src/common/models/User';
import { makeUser } from '../../../factory';

beforeAll(() => bootstrapForum());

function user(): User {
  return new User(makeUser({ id: '5', attributes: { slug: 'user5' } }));
}

function profileHref(): string {
  return m.route.prefix + app.route.user(user());
}

/**
 * The links a keyboard or screen reader user reaches.
 */
function reachableLinks(card: any): HTMLAnchorElement[] {
  return card.find('a').filter((a: HTMLAnchorElement) => a.getAttribute('tabindex') !== '-1' && a.getAttribute('aria-hidden') !== 'true');
}

describe('UserCard', () => {
  beforeAll(() => app.boot());

  it("links the user's name to their profile", () => {
    const card = mq(UserCard, { user: user() });

    expect(card.find('.UserCard-identity a')[0]?.getAttribute('href')).toBe(profileHref());
  });

  it('gives keyboard and screen reader users one link to the profile', () => {
    const card = mq(UserCard, { user: user() });

    const links = reachableLinks(card).filter((a) => a.getAttribute('href') === profileHref());

    expect(links).toHaveLength(1);
    expect(links[0].closest('.UserCard-identity')).not.toBeNull();
  });

  it('still links the avatar for mouse users', () => {
    const card = mq(UserCard, { user: user() });

    expect(card.find('a .UserCard-avatar')).toHaveLength(1);
  });

  it('does not link the name on an editable card', () => {
    const card = mq(UserCard, { user: user(), editable: true });

    expect(card).toHaveElement('.UserCard-identity');
    expect(card).not.toHaveElement('.UserCard-identity a');
  });
});
