import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import Comment from '../../../../src/forum/components/Comment';
import ItemList from '../../../../src/common/utils/ItemList';
import { app } from '../../../../src/forum';

beforeAll(() => bootstrapForum());

describe('Comment user card', () => {
  const attrs = (overrides: Record<string, unknown> = {}) => ({
    headerItems: new ItemList(),
    user: undefined,
    cardVisible: true,
    isEditing: false,
    isHidden: false,
    contentHtml: '<p>Hello</p>',
    ...overrides,
  });

  // The card stays visible while its user goes away: deleting a user from
  // their hover card removes the model from the store, so the post's user()
  // becomes undefined before the card has finished closing. A post by a user
  // who was deleted earlier has a user() of false.
  test.each([
    ['undefined', undefined],
    ['false', false],
  ])('no card is rendered, and nothing throws, when the user is %s', (_label, user) => {
    expect(() => mq(Comment, attrs({ user }))).not.toThrow();
    expect(mq(Comment, attrs({ user }))).not.toHaveElement('.UserCard');
  });

  test('the card is rendered for a user', () => {
    const [user] = [
      app.store.pushPayload({
        data: { type: 'users', id: '2', attributes: { username: 'ann', displayName: 'Ann' } },
      }),
    ] as any[];

    expect(mq(Comment, attrs({ user }))).toHaveElement('.UserCard');
  });

  test('no card is rendered while it is not visible', () => {
    expect(mq(Comment, attrs({ cardVisible: false }))).not.toHaveElement('.UserCard');
  });
});
