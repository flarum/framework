import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import commonExtenders from '../../../../src/common/extend';
import Dialog from '../../../../src/common/models/Dialog';
import DialogSection from '../../../../src/forum/components/DialogSection';

beforeAll(() => {
  bootstrapForum();
  app.boot();

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
