import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import commonExtenders from '../../../../src/common/extend';
import type Dialog from '../../../../src/common/models/Dialog';
import DialogListItem from '../../../../src/forum/components/DialogListItem';
import loadTranslations from '../../translations';

beforeAll(() => {
  // jsdom has no innerText, which a message's plain content is read from.
  if (!('innerText' in HTMLElement.prototype)) {
    Object.defineProperty(HTMLElement.prototype, 'innerText', {
      get() {
        return this.textContent;
      },
    });
  }

  bootstrapForum();
  app.boot();
  loadTranslations();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));

  // The forum's own route helper; its extenders also bring in flarum/deck's.
  (app.route as any).dialog = (dialog: Dialog) => '/messages/dialog/' + dialog.id();
});

let nextId = 700;

/** A conversation between the viewer (user 1) and others, its last message written by `lastBy`. */
function conversation(type: string, others: string[], lastBy: string, title: string): Dialog {
  const id = String(nextId++);

  app.store.pushPayload({
    data: {
      type: 'dialogs',
      id,
      attributes: { type, title, lastMessageAt: new Date().toISOString(), unreadCount: 0 },
      relationships: {
        users: { data: ['1', ...others].map((userId) => ({ type: 'users', id: userId })) },
        lastMessage: { data: { type: 'dialog-messages', id } },
      },
    },
    included: [
      ...others.map((userId) => ({ type: 'users', id: userId, attributes: { username: 'user' + userId, displayName: 'user' + userId } })),
      {
        type: 'dialog-messages',
        id,
        attributes: { contentHtml: '<p>See you there</p>' },
        relationships: { user: { data: { type: 'users', id: lastBy } } },
      },
    ],
  } as any);

  return app.store.getById<Dialog>('dialogs', id)!;
}

describe('a group in the conversation list', () => {
  it('is named by its title', () => {
    const item = mq(DialogListItem, { dialog: conversation('group', ['31', '32', '33'], '31', 'user31, user32, user33') });

    item.should.contain('user31, user32, user33');
  });

  it('shows two of its members, one over the other', () => {
    const item = mq(DialogListItem, { dialog: conversation('group', ['31', '32', '33'], '31', 'user31, user32, user33') });

    item.should.have(2, '.DialogListItem-avatar--group .Avatar');
  });

  // Everyone else has left: one avatar, full size, not half a stack.
  it('shows a group down to one other member with their avatar alone', () => {
    const item = mq(DialogListItem, { dialog: conversation('group', ['35'], '35', 'user35') });

    item.should.not.have('.DialogListItem-avatar--group');
    item.should.have(1, '.DialogListItem-avatar .Avatar');
  });

  it('says who wrote the last message', () => {
    const item = mq(DialogListItem, { dialog: conversation('group', ['31', '32'], '32', 'user31, user32') });

    item.should.contain('user32: See you there');
  });

  it("still says when the last message is the viewer's own", () => {
    const item = mq(DialogListItem, { dialog: conversation('group', ['31', '32'], '1', 'user31, user32') });

    item.should.contain('You: See you there');
  });
});

describe('a direct conversation in the list', () => {
  it("is named by the other member, with their avatar, and the preview isn't prefixed", () => {
    const item = mq(DialogListItem, { dialog: conversation('direct', ['34'], '34', 'Messaging user34') });

    item.should.contain('user34');
    item.should.not.have('.DialogListItem-avatar--group');
    item.should.not.contain('user34: See you there');
  });
});
