import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import commonExtenders from '../../../../src/common/extend';
import type Dialog from '../../../../src/common/models/Dialog';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));
});

let nextId = 600;

/** A conversation between the viewer (user 1) and the given others. */
function conversation(type: string, others: string[]): Dialog {
  const id = String(nextId++);

  app.store.pushPayload({
    data: {
      type: 'dialogs',
      id,
      attributes: { type },
      relationships: { users: { data: ['1', ...others].map((userId) => ({ type: 'users', id: userId })) } },
    },
    included: others.map((userId) => ({ type: 'users', id: userId, attributes: { username: 'user' + userId } })),
  } as any);

  return app.store.getById<Dialog>('dialogs', id)!;
}

describe('a conversation', () => {
  it('is a group when the server says so', () => {
    expect(conversation('group', ['21', '22']).isGroup()).toBe(true);
    expect(conversation('direct', ['21']).isGroup()).toBe(false);
  });

  it("lists everyone in it but the viewer as who it's with", () => {
    expect(
      conversation('group', ['21', '22', '23'])
        .recipients()
        .map((user) => user.id())
    ).toEqual(['21', '22', '23']);
  });

  it('has no recipients until its members are known', () => {
    app.store.pushPayload({ data: { type: 'dialogs', id: '699', attributes: { type: 'group' } } } as any);

    expect(app.store.getById<Dialog>('dialogs', '699')!.recipients()).toEqual([]);
  });
});
