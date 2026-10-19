import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Stream from 'flarum/common/utils/Stream';
import Dialog from '../../../../src/common/models/Dialog';
import DialogMessage from '../../../../src/common/models/DialogMessage';
import DialogListState from '../../../../src/forum/states/DialogListState';
import MessagesPage from '../../../../src/forum/components/MessagesPage';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;
});

let dialog: Dialog;

beforeEach(() => {
  (app as any).dialogs = new DialogListState({});

  dialog = app.store.pushObject<Dialog>({ type: 'dialogs', id: '1', attributes: {} } as any)!;
  (app.dialogs as any).pages = [{ number: 1, items: [dialog] }];
});

/** The page once its list has loaded, without running its lifecycle. */
function page(state: { selectedDialog?: Dialog | null; loadingDialog?: boolean } = {}): MessagesPage {
  return Object.assign(Object.create(MessagesPage.prototype), {
    selectedDialog: Stream(state.selectedDialog ?? null),
    loadingDialog: state.loadingDialog ?? false,
    currentDialogId: null,
  });
}

/** What the page shows side by side, by key. */
const shown = (page: MessagesPage) => (page.view() as any).children[0].children.map((child: any) => child.key);

describe('the messages page, once the list has loaded', () => {
  // As a phone leaves it, rather than open a conversation nobody has seen.
  it('shows the list on its own when no conversation is open', () => {
    expect(shown(page())).toEqual(['sidebar']);
  });

  it('shows the open conversation beside the list', () => {
    expect(shown(page({ selectedDialog: dialog }))).toEqual(['sidebar', 'dialog']);
  });

  it('shows the conversation loading beside the list', () => {
    expect(shown(page({ loadingDialog: true }))).toEqual(['sidebar', 'loading']);
  });
});
