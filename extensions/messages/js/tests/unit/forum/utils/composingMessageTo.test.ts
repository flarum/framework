import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import ComposerState from 'flarum/forum/states/ComposerState';
import Dialog from '../../../../src/common/models/Dialog';
import MessageComposer from '../../../../src/forum/components/MessageComposer';
import composingMessageTo from '../../../../src/forum/utils/composingMessageTo';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
});

// The test setup answers every lookup; the forum's registry finds the composer
// only under the extension's own namespace, where its bundle registers it.
beforeEach(() => {
  jest
    .spyOn(flarum.reg, 'checkModule')
    .mockImplementation((namespace: string, id: string) =>
      namespace === 'flarum-messages' && id === 'forum/components/MessageComposer' ? MessageComposer : false
    );
});

afterEach(() => jest.restoreAllMocks());

const dialog = (id: string) => app.store.pushObject<Dialog>({ type: 'dialogs', id, attributes: {} } as any)!;

function open(replyingTo: Dialog) {
  app.composer.body = { componentClass: MessageComposer, attrs: { replyingTo } } as any;
  app.composer.position = ComposerState.Position.NORMAL;
}

describe('composingMessageTo', () => {
  it('is true while the composer holds a reply to that conversation', () => {
    const one = dialog('1');
    open(one);

    expect(composingMessageTo(one)).toBe(true);
  });

  it('is false for a reply to another conversation', () => {
    open(dialog('1'));

    expect(composingMessageTo(dialog('2'))).toBe(false);
  });

  it('is false once the composer is hidden', () => {
    const one = dialog('1');
    open(one);
    app.composer.position = ComposerState.Position.HIDDEN;

    expect(composingMessageTo(one)).toBe(false);
  });
});
