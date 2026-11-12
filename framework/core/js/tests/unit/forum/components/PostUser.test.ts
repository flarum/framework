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

  test('asks for a third level heading below a heading of its own', () => {
    app.store.pushPayload({ data: { type: 'users', id: '1', attributes: { username: 'alice', displayName: 'alice' } } });
    const post = { user: () => app.store.getById('users', '1') } as any;

    expect(mq(PostUser, { post, headingLevel: 3 })).toHaveElement('h3.PostUser-name a');
    expect(mq(PostUser, { post, headingLevel: 3 })).not.toHaveElement('h2.PostUser-name');
  });

  test('a deleted user follows the requested level too', () => {
    const post = { user: () => false } as any;

    expect(mq(PostUser, { post, headingLevel: 3 })).toHaveElement('h3.PostUser-name');
  });

  describe('source byline', () => {
    const postWith = (attributes: Record<string, unknown>) => {
      app.store.pushPayload({ data: { type: 'users', id: '1', attributes: { username: 'alice', displayName: 'alice' } } });
      app.store.pushPayload({
        data: { type: 'posts', id: '10', attributes, relationships: { user: { data: { type: 'users', id: '1' } } } },
      });

      return app.store.getById('posts', '10')!;
    };

    test('posts made in the forum have none', () => {
      expect(mq(PostUser, { post: postWith({ source: null, sourceName: null }) })).not.toHaveElement('.PostUser-source');
    });

    test('is absent when the forum does not send sources', () => {
      expect(mq(PostUser, { post: postWith({ source: undefined, sourceName: undefined }) })).not.toHaveElement('.PostUser-source');
    });

    test('shows API posts', () => {
      expect(mq(PostUser, { post: postWith({ source: 'api', sourceName: null }) })).toHaveElement('.PostUser-source');
      expect(PostUser.prototype.sourceLabel(postWith({ source: 'api', sourceName: null }))).toBe('API');
      expect(PostUser.prototype.sourceTooltip(postWith({ source: 'api', sourceName: null }))).toBe('Posted through the API');
    });

    test('prefers the stored name', () => {
      expect(PostUser.prototype.sourceLabel(postWith({ source: 'mcp', sourceName: 'Team Assistant' }))).toBe('Team Assistant');
    });

    test('other sources get the generic tooltip', () => {
      expect(PostUser.prototype.sourceTooltip(postWith({ source: 'mcp', sourceName: 'MCP' }))).toBe('Posted through a connected app');
    });

    test('models other than posts, such as messages, have none', () => {
      const message = { user: () => app.store.getById('users', '1'), source: () => 'api', sourceName: () => null } as any;

      expect(mq(PostUser, { post: message })).not.toHaveElement('.PostUser-source');
    });
  });
});
