import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import CommentPost from '../../../../src/forum/components/CommentPost';
import app from '../../../../src/forum/app';

beforeAll(() => bootstrapForum());

describe('CommentPost avatar', () => {
  const avatarFor = (user: unknown) => {
    const post = { user: () => user } as any;

    return mq({ view: () => (CommentPost.prototype as any).avatar.call({ attrs: { post } }) });
  };

  test('the avatar link is hidden from assistive tech and the tab order', () => {
    app.store.pushPayload({ data: { type: 'users', id: '1', attributes: { username: 'alice', displayName: 'alice' } } });
    const user = app.store.getById('users', '1');

    const rendered = avatarFor(user);

    expect(rendered).toHaveElement('a[aria-hidden=true][tabindex="-1"] .Post-avatar');
  });

  test('a deleted user has no link', () => {
    expect(avatarFor(false)).not.toHaveElement('a');
  });
});
