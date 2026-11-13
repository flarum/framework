import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import type NotificationModel from 'flarum/common/models/Notification';
import commonExtenders from '../../../../src/common/extend';
import MessageLikedNotification from '../../../../src/forum/components/MessageLikedNotification';
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

  // The routes the forum registers; stood in for here.
  app.routes['messages'] = { path: '/messages' } as any;
  app.routes['dialog.message'] = { path: '/messages/dialog/:id/:near' } as any;
});

/** A messageLiked notification as the API sends it: the message, but not its conversation. */
function liked(id: string, dialog: { type: string; id: string } | null): NotificationModel {
  app.store.pushPayload({
    data: {
      type: 'notifications',
      id,
      attributes: { contentType: 'messageLiked', createdAt: new Date().toISOString(), isRead: false },
      relationships: {
        fromUser: { data: { type: 'users', id: '41' } },
        subject: { data: { type: 'dialog-messages', id } },
      },
    },
    included: [
      { type: 'users', id: '41', attributes: { username: 'bob', displayName: 'bob' } },
      {
        type: 'dialog-messages',
        id,
        attributes: { number: 3, contentHtml: '<p>Lunch on Friday?</p>', createdAt: new Date().toISOString() },
        relationships: { dialog: { data: dialog } },
      },
    ],
  } as any);

  return app.store.getById<NotificationModel>('notifications', id)!;
}

/** Where the notification links to. Link doesn't render its href under mithril-query. */
function href(notification: NotificationModel): string {
  const component = new MessageLikedNotification();
  component.attrs = { notification };

  return component.href();
}

describe('a notification that a message was liked', () => {
  it('says who liked it, and what', () => {
    const output = mq(MessageLikedNotification, { notification: liked('501', { type: 'dialogs', id: '7' }) });

    output.should.contain('bob liked your message');
    output.should.contain('Lunch on Friday?');
  });

  it('links to the message in its conversation, which the API does not include', () => {
    const notification = liked('502', { type: 'dialogs', id: '7' });

    expect(app.store.getById('dialogs', '7')).toBeUndefined();
    expect(href(notification)).toBe('/messages/dialog/7/3');
  });

  it('links to the messages page without a conversation to open', () => {
    expect(href(liked('503', null))).toBe('/messages');
  });
});
