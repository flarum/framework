import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import Stream from 'flarum/common/utils/Stream';
import type User from 'flarum/common/models/User';
import type { IUserSelectionModalAttrs } from 'flarum/common/components/UserSelectionModal';
import commonExtenders from '../../../../src/common/extend';
import MessageComposer from '../../../../src/forum/components/MessageComposer';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));

  app.store.pushPayload({
    data: [
      { type: 'users', id: '2', attributes: { username: 'carol', canSendAnyMessage: false } },
      { type: 'users', id: '3', attributes: { username: 'dave', canSendAnyMessage: true } },
      // Someone the server said nothing about either way.
      { type: 'users', id: '4', attributes: { username: 'erin' } },
      // An account nobody can write to, such as one anonymised by flarum/gdpr.
      { type: 'users', id: '5', attributes: { username: 'Anonymous1', canMessage: false, canSendAnyMessage: false } },
    ],
  } as any);
});

beforeEach(() => app.session.user!.pushAttributes({ canSendAnyMessage: true, canMessageUsersWithoutPermission: false }));
afterEach(() => jest.restoreAllMocks());

const user = (id: string) => app.store.getById<User>('users', id)!;

/**
 * Open the recipient picker from a new message's composer, and return what the
 * composer handed it. Only the header is rendered: the editor below it builds
 * itself on a timer jsdom can't host.
 */
function picker(): IUserSelectionModalAttrs {
  const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});

  const composer = new (MessageComposer as any)();
  composer.attrs = { composer: app.composer };
  composer.recipients = Stream([]);

  mq(m('ul', composer.headerItems().toArray())).click('.MessageComposer-recipients .Button');

  return show.mock.calls[0][1] as IUserSelectionModalAttrs;
}

function excludes(attrs: IUserSelectionModalAttrs, user: User): boolean {
  const excluded = attrs.excluded;

  return typeof excluded === 'function' ? excluded(user) : !!excluded?.map(String).includes(user.id()!);
}

describe('who is listed to message', () => {
  it('leaves out the sender', () => {
    expect(excludes(picker(), app.session.user!)).toBe(true);
  });

  it("leaves out anyone who can't be messaged at all", () => {
    expect(excludes(picker(), user('5'))).toBe(true);
  });

  it('leaves them out for those allowed to message users without messaging permission too', () => {
    app.session.user!.pushAttributes({ canMessageUsersWithoutPermission: true });

    expect(excludes(picker(), user('5'))).toBe(true);
  });

  it('lists everyone else', () => {
    expect(excludes(picker(), user('2'))).toBe(false);
    expect(excludes(picker(), user('3'))).toBe(false);
    expect(excludes(picker(), user('4'))).toBe(false);
  });
});

describe('picking who to message', () => {
  it("marks members who can't reply as unavailable, with the reason", () => {
    expect(picker().unavailable?.(user('2'))).toBeTruthy();
  });

  it('leaves members who can reply available', () => {
    expect(picker().unavailable?.(user('3'))).toBeFalsy();
  });

  // The server has the final say when the message is sent.
  it("leaves members it wasn't told about available", () => {
    expect(picker().unavailable?.(user('4'))).toBeFalsy();
  });

  it('leaves everyone available to those allowed to message users without messaging permission', () => {
    app.session.user!.pushAttributes({ canMessageUsersWithoutPermission: true });

    expect(picker().unavailable?.(user('2'))).toBeFalsy();
  });
});
