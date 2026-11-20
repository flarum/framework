import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import commonExtenders from '../../../../src/common/extend';
import type Dialog from '../../../../src/common/models/Dialog';
import type DialogMessage from '../../../../src/common/models/DialogMessage';
import Message from '../../../../src/forum/components/Message';
import MessageStreamState from '../../../../src/forum/states/MessageStreamState';
import { onReadSignal } from '../../../../src/forum/utils/readReceipts';
import loadTranslations from '../../translations';

/**
 * A message already on screen shows a read as it happens. A message only
 * redraws when something it watches changes, and someone reading it changes
 * nothing on the message itself, only on its conversation.
 */

beforeAll(() => {
  bootstrapForum();
  app.boot();
  loadTranslations();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));

  // Registered by the forum's extenders, for each message's permalink.
  (app.route as any).dialog = () => '/messages/dialog/70';
});

function conversation(readers: { userId: string; messageId: number }[]): { dialog: Dialog; message: DialogMessage } {
  app.store.pushPayload({
    data: {
      type: 'dialog-messages',
      id: '700',
      attributes: { type: 'comment', number: 1, contentHtml: '<p>Hi Bob</p>', createdAt: new Date().toISOString() },
      relationships: { user: { data: { type: 'users', id: app.session.user!.id() } }, dialog: { data: { type: 'dialogs', id: '70' } } },
    },
    included: [{ type: 'dialogs', id: '70', attributes: { type: 'direct', readReceipts: { members: 1, after: 0, readers } } }],
  } as any);

  return { dialog: app.store.getById<Dialog>('dialogs', '70')!, message: app.store.getById<DialogMessage>('dialog-messages', '700')! };
}

it('shows a read that arrives while the message is on screen', () => {
  const { message } = conversation([]);
  const rendered = mq(Message, { message, state: new MessageStreamState({}) });

  rendered.should.have('.MessageReadStatus--sent');

  onReadSignal({ dialogId: '70', userId: '2', messageId: 700 });
  // Bob wasn't among the readers before: the receipts are loaded again,
  // which a test has nowhere to load from. As they would come back:
  app.store.getById<Dialog>('dialogs', '70')!.pushAttributes({ readReceipts: { members: 1, after: 0, readers: [{ userId: '2', messageId: 700 }] } });
  rendered.redraw();

  rendered.should.have('.MessageReadStatus--all');
  rendered.should.not.have('.MessageReadStatus--sent');
});
