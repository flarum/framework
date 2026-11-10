import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import GlobalSearch from 'flarum/forum/components/GlobalSearch';
import Dialog from '../../../../src/common/models/Dialog';
import DialogMessage from '../../../../src/common/models/DialogMessage';
import GlobalMessagesSearchSource from '../../../../src/forum/components/GlobalMessagesSearchSource';
import addMessageSearch from '../../../../src/forum/addMessageSearch';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  addMessageSearch();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;
});

afterEach(() => jest.restoreAllMocks());

const sourceKeys = () => Object.keys(Object.create(GlobalSearch.prototype).sourceItems().toObject());

describe('the Messages search category', () => {
  it('is offered to members', () => {
    expect(sourceKeys()).toContain('messages');
  });

  // Guests have no conversations to search.
  it('is not offered to guests at all', () => {
    const user = app.session.user;
    (app.session as any).user = null;

    try {
      expect(sourceKeys()).not.toContain('messages');
    } finally {
      (app.session as any).user = user;
    }
  });
});

describe('GlobalMessagesSearchSource', () => {
  it("asks for the member's messages that match, with who each conversation is with", async () => {
    const find = jest.spyOn(app.store, 'find').mockResolvedValue([] as never);

    await new GlobalMessagesSearchSource().search('Pineapple', 5);

    expect(find).toHaveBeenCalledWith('dialog-messages', {
      filter: { q: 'pineapple' },
      page: { limit: 5 },
      include: 'user,dialog.users',
    });
  });

  it('goes to the message in its conversation', () => {
    app.store.pushPayload({
      data: { type: 'dialog-messages', id: '31', attributes: { number: 4 }, relationships: { dialog: { data: { type: 'dialogs', id: '7' } } } },
      included: [{ type: 'dialogs', id: '7', attributes: {} }],
    } as any);
    // The helper messages registers for its routes; stood in for here.
    const route = jest.fn(() => '/messages/dialog/7/4');
    (app.route as any).dialog = route;

    expect(new GlobalMessagesSearchSource().gotoItem('31')).toBe('/messages/dialog/7/4');
    expect(route).toHaveBeenCalledWith(app.store.getById('dialogs', '7'), 4);
  });

  it('has nowhere to go for a message it has not loaded', () => {
    expect(new GlobalMessagesSearchSource().gotoItem('999')).toBeNull();
  });

  it('has no page of its own to list every result on', () => {
    expect(new GlobalMessagesSearchSource().fullPage()).toBeNull();
  });
});
