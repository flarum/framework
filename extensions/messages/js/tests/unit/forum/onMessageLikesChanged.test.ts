import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Dialog from '../../../src/common/models/Dialog';
import DialogMessage from '../../../src/common/models/DialogMessage';
import onMessageLikesChanged from '../../../src/forum/onMessageLikesChanged';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;
});

/** A message as realtime sends it after a like. */
const liked = (id: string, likers: string[]) => ({
  data: {
    type: 'dialog-messages',
    id,
    attributes: { likesCount: likers.length },
    relationships: { likes: { data: likers.map((liker) => ({ type: 'users', id: liker })) } },
  },
  included: likers.map((liker) => ({ type: 'users', id: liker, attributes: { username: `user${liker}` } })),
});

describe('onMessageLikesChanged', () => {
  it('shows the new likes on a message this browser has', () => {
    app.store.pushObject({ type: 'dialog-messages', id: '700', attributes: { likesCount: 0 }, relationships: { likes: { data: [] } } } as any);

    onMessageLikesChanged(liked('700', ['2']));

    const message = app.store.getById<DialogMessage>('dialog-messages', '700')!;
    expect(message.likesCount()).toBe(1);
    expect((message.likes() || []).map((user) => user && user.id())).toEqual(['2']);
  });

  it('leaves alone a message this browser has not loaded', () => {
    onMessageLikesChanged(liked('701', ['2']));

    expect(app.store.getById('dialog-messages', '701')).toBeUndefined();
  });

  it('ignores a payload without a message', () => {
    expect(() => onMessageLikesChanged({})).not.toThrow();
  });
});
