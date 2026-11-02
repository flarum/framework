import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import ReplyPlaceholder from '../../../../src/forum/components/ReplyPlaceholder';
import app from '../../../../src/forum/app';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  app.store.pushPayload({ data: { type: 'users', id: '1', attributes: { username: 'alice', displayName: 'alice' } } });
  app.session.user = app.store.getById('users', '1')!;
});

describe('ReplyPlaceholder', () => {
  const composing = { discussion: {} as any, composingReply: () => true };

  // The discussion page has an <h1> title, so the author being composed is an <h2> to keep the heading order valid.
  test('the author name is a second level heading while composing', () => {
    expect(mq(ReplyPlaceholder, composing)).toHaveElement('h2.PostUser-name');
  });

  test('the author name follows the requested level while composing', () => {
    const out = mq(ReplyPlaceholder, { ...composing, headingLevel: 3 });

    expect(out).toHaveElement('h3.PostUser-name');
    expect(out).not.toHaveElement('h2.PostUser-name');
  });

  test('has no heading while it is only a placeholder', () => {
    expect(mq(ReplyPlaceholder, { discussion: {} as any, composingReply: () => false })).not.toHaveElement('.PostUser-name');
  });
});
