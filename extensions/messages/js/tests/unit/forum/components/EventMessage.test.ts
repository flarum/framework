import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import commonExtenders from '../../../../src/common/extend';
import type DialogMessage from '../../../../src/common/models/DialogMessage';
import EventMessage from '../../../../src/forum/components/EventMessage';
import Message from '../../../../src/forum/components/Message';
import MessageStream from '../../../../src/forum/components/MessageStream';
import describeEvent from '../../../../src/forum/utils/describeEvent';
import loadTranslations from '../../translations';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  loadTranslations();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));
});

let nextId = 1000;

/** A notice in a conversation, about user 51 ("bob"). */
function notice(type: string, eventData: object = {}): DialogMessage {
  const id = String(nextId++);

  app.store.pushPayload({
    data: {
      type: 'dialog-messages',
      id,
      attributes: { type, eventData, number: 2, createdAt: new Date().toISOString() },
      relationships: { user: { data: { type: 'users', id: '51' } } },
    },
    included: [{ type: 'users', id: '51', attributes: { username: 'bob', displayName: 'bob', slug: 'bob' } }],
  } as any);

  return app.store.getById<DialogMessage>('dialog-messages', id)!;
}

describe('a message', () => {
  it('is a comment unless the server says it is a notice', () => {
    app.store.pushPayload({ data: { type: 'dialog-messages', id: '999', attributes: { contentHtml: '<p>Hi</p>' } } } as any);

    expect(app.store.getById<DialogMessage>('dialog-messages', '999')!.isComment()).toBe(true);
    expect(notice('userLeft').isComment()).toBe(false);
  });
});

describe('a notice', () => {
  it('says who left', () => {
    mq(EventMessage, { message: notice('userLeft') }).should.contain('bob left the conversation');
  });

  it('says who named the group, and what', () => {
    mq(EventMessage, { message: notice('renamed', { name: 'Ski trip' }) }).should.contain('bob named the conversation “Ski trip”');
  });

  // The whole sentence through the plain description: mithril-query reads an
  // apostrophe in contain()'s text as part of a selector.
  it("says who took the group's name away", () => {
    const message = notice('renamed', { name: null });

    mq(EventMessage, { message }).should.contain('bob removed the conversation');
    expect(describeEvent(message, true)).toBe("bob removed the conversation's name");
  });

  it('is described in plain words for a preview', () => {
    expect(describeEvent(notice('userLeft'), true)).toBe('bob left the conversation');
  });
});

describe('the stream', () => {
  const itemFor = (message: DialogMessage) =>
    (MessageStream.prototype.messageItem.call({ attrs: {}, timeGap: () => null } as any, message) as any).children.find((child: any) => child?.tag);

  it('shows a notice as a notice', () => {
    expect(itemFor(notice('userLeft')).tag).toBe(EventMessage);
  });

  it('shows what a member wrote as a message', () => {
    app.store.pushPayload({ data: { type: 'dialog-messages', id: '998', attributes: { type: 'comment', contentHtml: '<p>Hi</p>' } } } as any);

    expect(itemFor(app.store.getById<DialogMessage>('dialog-messages', '998')!).tag).toBe(Message);
  });
});
