import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
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

describe('the messages page, for a guest', () => {
  beforeEach(() => {
    app.session.user = null;
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
  });

  // As from the link in a new message email, while logged out.
  it('asks them to log in where they are', () => {
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    const set = jest.spyOn(m.route, 'set').mockImplementation(() => {});

    mq(MessagesPage, { routeName: 'dialog' });
    jest.runAllTimers();

    expect(show).toHaveBeenCalled();
    expect(set).not.toHaveBeenCalled();
  });
});
