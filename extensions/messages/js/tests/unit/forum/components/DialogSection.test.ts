import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import DialogListState from '../../../../src/forum/states/DialogListState';
import DetailsModal from '../../../../src/forum/components/DetailsModal';
import loadTranslations from '../../translations';
import commonExtenders from '../../../../src/common/extend';
import Dialog from '../../../../src/common/models/Dialog';
import DialogSection from '../../../../src/forum/components/DialogSection';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  loadTranslations();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));
});

beforeEach(() => app.session.user!.pushAttributes({ canSendAnyMessage: true }));

let nextId = 400;

/**
 * A conversation between the viewer and someone else, as the dialog's own
 * endpoint describes it to the viewer.
 */
function conversation(recipient: { canSendAnyMessage: boolean }, dialog: { canSendMessage: boolean; anyoneCanReply: boolean }): Dialog {
  const id = String(nextId++);

  app.store.pushPayload({
    data: {
      type: 'dialogs',
      id,
      attributes: dialog,
      relationships: {
        users: {
          data: [
            { type: 'users', id: '1' },
            { type: 'users', id },
          ],
        },
      },
    },
    included: [{ type: 'users', id, attributes: { username: 'user' + id, ...recipient } }],
  } as any);

  return app.store.getById<Dialog>('dialogs', id)!;
}

const saysCannotReply = (dialog: Dialog) => DialogSection.prototype.recipientCannotReply.call({ attrs: { dialog } } as any);

describe("the conversation's 'cannot reply' notice", () => {
  it("shows when the other member can't reply and the conversation is closed to them", () => {
    expect(saysCannotReply(conversation({ canSendAnyMessage: false }, { canSendMessage: false, anyoneCanReply: false }))).toBe(true);
  });

  // An account anonymised by flarum/gdpr: nobody can write to it, however
  // open the conversation was.
  it("shows when the conversation was opened to replies but the viewer still can't send", () => {
    expect(saysCannotReply(conversation({ canSendAnyMessage: false }, { canSendMessage: false, anyoneCanReply: true }))).toBe(true);
  });

  it('does not show when the viewer can send here anyway', () => {
    expect(saysCannotReply(conversation({ canSendAnyMessage: false }, { canSendMessage: true, anyoneCanReply: false }))).toBe(false);
    expect(saysCannotReply(conversation({ canSendAnyMessage: false }, { canSendMessage: true, anyoneCanReply: true }))).toBe(false);
  });

  it('does not show between two members who can both send', () => {
    expect(saysCannotReply(conversation({ canSendAnyMessage: true }, { canSendMessage: true, anyoneCanReply: false }))).toBe(false);
  });

  // They aren't told whether anyone else can send.
  it("does not show to a viewer who can't send messages themselves", () => {
    app.session.user!.pushAttributes({ canSendAnyMessage: false });

    expect(saysCannotReply(conversation({ canSendAnyMessage: false }, { canSendMessage: false, anyoneCanReply: false }))).toBe(false);
  });
});

/** A group of the viewer and the given others, as the dialog's own endpoint describes it to the viewer. */
function group(others: { canSendAnyMessage: boolean }[], dialog: { canSendMessage: boolean; anyoneCanReply: boolean } = { canSendMessage: true, anyoneCanReply: false }): Dialog {
  const id = String(nextId++);
  const userIds = others.map(() => String(nextId++));

  app.store.pushPayload({
    data: {
      type: 'dialogs',
      id,
      attributes: { ...dialog, type: 'group', title: userIds.map((userId) => 'user' + userId).join(', ') },
      relationships: { users: { data: ['1', ...userIds].map((userId) => ({ type: 'users', id: userId })) } },
    },
    included: userIds.map((userId, i) => ({ type: 'users', id: userId, attributes: { username: 'user' + userId, ...others[i] } })),
  } as any);

  return app.store.getById<Dialog>('dialogs', id)!;
}

/** A DialogSection's methods, without the message stream its view starts. */
function section(dialog: Dialog, onback = jest.fn()) {
  const instance = Object.create(DialogSection.prototype);
  instance.attrs = { dialog, onback };

  return instance as DialogSection & { attrs: { dialog: Dialog; onback: jest.Mock } };
}

describe('a group conversation', () => {
  afterEach(() => jest.restoreAllMocks());

  // Others can still answer: one member who can't is nothing to point out.
  it("never says a member can't reply", () => {
    expect(saysCannotReply(group([{ canSendAnyMessage: false }, { canSendAnyMessage: true }], { canSendMessage: false, anyoneCanReply: false }))).toBe(false);
  });

  it('is headed with its title and how many are in it', () => {
    const header = mq(m('div', section(group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }])).headerInfo()));

    header.should.have('.DialogSection-header-info-title');
    header.should.contain('3 members');
  });

  it('shows who is in it when the member count is clicked', () => {
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});
    const dialog = group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }]);

    mq(m('div', section(dialog).headerInfo())).click('.DialogSection-members');

    expect(show).toHaveBeenCalledWith(DetailsModal, { dialog });
  });

  it('can be left', () => {
    expect(section(group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }])).controlItems().has('leave')).toBe(true);
  });

  it('is left only once the member confirms, and then closed', async () => {
    app.dialogs = new DialogListState({}, 1);
    app.dropdownDialogs = new DialogListState({}, 1, 5);
    const request = jest.spyOn(app, 'request').mockImplementation((() => Promise.resolve(null)) as any);
    const confirm = jest.spyOn(window, 'confirm');
    const target = section(group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }]));

    confirm.mockReturnValue(false);
    await target.leave();
    expect(request).not.toHaveBeenCalled();

    confirm.mockReturnValue(true);
    await target.leave();
    expect(request).toHaveBeenCalledTimes(1);
    expect(target.attrs.onback).toHaveBeenCalled();
  });
});

describe('renaming a group', () => {
  it('is offered where the server allows it', () => {
    const dialog = group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }]);

    dialog.pushAttributes({ canRename: true });
    expect(section(dialog).controlItems().has('rename')).toBe(true);

    dialog.pushAttributes({ canRename: false });
    expect(section(dialog).controlItems().has('rename')).toBe(false);
  });
});

describe('a direct conversation', () => {
  it("can't be left: there would be nobody in it", () => {
    expect(section(conversation({ canSendAnyMessage: true }, { canSendMessage: true, anyoneCanReply: false })).controlItems().has('leave')).toBe(false);
  });
});

describe('a conversation addressed to a Flarum Group', () => {
  it('shows the Group and opens participant details without claiming the individual list is its whole audience', () => {
    const dialog = group([]);
    app.store.pushPayload({ data: { type: 'groups', id: '4', attributes: { namePlural: 'Moderators', nameSingular: 'Moderator', icon: 'fas fa-users' } } } as any);
    dialog.pushData({ relationships: { groups: { data: [{ type: 'groups', id: '4' }] } } });
    const header = mq(m('div', section(dialog).headerInfo()));
    header.should.contain('Participants');
    header.should.not.contain('1 member');
    const details = new (DetailsModal as any)();
    details.attrs = { dialog };
    mq(m('div', details.content())).should.contain('Moderators');
  });
});

describe("a group conversation's header avatars", () => {
  const avatars = (dialog: Dialog) => mq(m('div', section(dialog).headerAvatar()));

  it('shows its Groups first, then its members, three in all, and counts the rest', () => {
    const dialog = group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }, { canSendAnyMessage: true }]);
    app.store.pushPayload({
      data: [
        { type: 'groups', id: '1', attributes: { namePlural: 'Admins', nameSingular: 'Admin', icon: 'fas fa-wrench' } },
        { type: 'groups', id: '5', attributes: { namePlural: 'Team', nameSingular: 'Team member', icon: 'fas fa-comment' } },
      ],
    } as any);
    dialog.pushData({ relationships: { groups: { data: ['1', '5'].map((id) => ({ type: 'groups', id })) } } });

    const header = avatars(dialog);

    header.should.have(2, '.DialogSection-header-group');
    header.should.have(1, '.Avatar');
    header.should.contain('+2');
  });

  it('shows only members when no Group is addressed, and nothing to count when they fit', () => {
    const header = avatars(group([{ canSendAnyMessage: true }, { canSendAnyMessage: true }]));

    header.should.not.have('.DialogSection-header-group');
    header.should.have(2, '.Avatar');
    header.should.not.have('.DialogSection-header-more');
  });
});
