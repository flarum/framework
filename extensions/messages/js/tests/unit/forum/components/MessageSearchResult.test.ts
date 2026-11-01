import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import commonExtenders from '../../../../src/common/extend';
import type Dialog from '../../../../src/common/models/Dialog';
import type DialogMessage from '../../../../src/common/models/DialogMessage';
import MessageSearchResult from '../../../../src/forum/components/MessageSearchResult';
import loadTranslations from '../../translations';

beforeAll(() => {
  if (!('innerText' in HTMLElement.prototype)) {
    // jsdom has no innerText, which a message's plain content is read from.
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

  // The helper the forum registers for its routes; stood in for here.
  (app.route as any).dialog = (dialog: Dialog) => '/messages/dialog/' + dialog.id();
});

/** A message found in a conversation of the given type with users 41 and 42. */
function found(type: string, others: string[]): DialogMessage {
  app.store.pushPayload({
    data: {
      type: 'dialog-messages',
      id: '90' + others.length,
      attributes: { number: 1, contentHtml: '<p>Pineapple</p>', createdAt: new Date().toISOString() },
      relationships: { dialog: { data: { type: 'dialogs', id: '90' + others.length } }, user: { data: { type: 'users', id: others[0] } } },
    },
    included: [
      {
        type: 'dialogs',
        id: '90' + others.length,
        attributes: { type, title: others.map((id) => 'user' + id).join(', ') },
        relationships: { users: { data: ['1', ...others].map((id) => ({ type: 'users', id })) } },
      },
      ...others.map((id) => ({ type: 'users', id, attributes: { username: 'user' + id, displayName: 'user' + id } })),
    ],
  } as any);

  return app.store.getById<DialogMessage>('dialog-messages', '90' + others.length)!;
}

describe('a message found by search', () => {
  it('says which group it is in, by all its members', () => {
    mq(MessageSearchResult, { message: found('group', ['41', '42']), query: 'pine' }).should.contain('Conversation with user41, user42');
  });

  it('says who a direct conversation is with', () => {
    mq(MessageSearchResult, { message: found('direct', ['43']), query: 'pine' }).should.contain('Conversation with user43');
  });
});
