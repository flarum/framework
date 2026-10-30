import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import PostUser from '../../../../src/forum/components/PostUser';
import app from '../../../../src/forum/app';

beforeAll(() => bootstrapForum());

describe('PostUser', () => {
  // The discussion page has an <h1> title, so post authors are <h2> to keep the heading order valid.
  test('the author name is a second level heading', () => {
    app.store.pushPayload({ data: { type: 'users', id: '1', attributes: { username: 'alice', displayName: 'alice' } } });
    const post = { user: () => app.store.getById('users', '1') } as any;

    expect(mq(PostUser, { post })).toHaveElement('h2.PostUser-name a');
  });

  test('a deleted user is a second level heading too', () => {
    const post = { user: () => false } as any;

    expect(mq(PostUser, { post })).toHaveElement('h2.PostUser-name');
  });
});
