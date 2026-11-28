import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import TextEditor from 'flarum/common/components/TextEditor';
import User from 'flarum/common/models/User';
import { jest } from '@jest/globals';
import m from 'mithril';
import addComposerAutocomplete from '../../../src/forum/addComposerAutocomplete';
import MentionFormats from '../../../src/forum/mentionables/formats/MentionFormats';
import UserMention from '../../../src/forum/mentionables/UserMention';
import PostMention from '../../../src/forum/mentionables/PostMention';
import GroupMention from '../../../src/forum/mentionables/GroupMention';

// Longer than the 250 ms throttle on mention searches, so its trailing call has run.
const AFTER_THROTTLE = 300;

let root: HTMLElement;
let textarea: HTMLTextAreaElement;
let resolveSearch: (users: User[]) => void;
let search: jest.SpiedFunction<UserMention['search']>;

const wait = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

// BasicEditorDriver runs the input listeners on both `input` and `keyup` for each keystroke.
function type(value: string) {
  textarea.value = value;
  textarea.setSelectionRange(value.length, value.length);
  textarea.dispatchEvent(new Event('input'));
  textarea.dispatchEvent(new KeyboardEvent('keyup'));
}

function dropdownText(): string | null {
  const dropdown = root.querySelector<HTMLElement>('.MentionsDropdown');

  return dropdown && dropdown.style.display !== 'none' ? dropdown.textContent : null;
}

function adrian() {
  return new User({ type: 'users', id: '7', attributes: { username: 'adrian', displayName: 'adrian' } }, app.store);
}

beforeAll(() => {
  bootstrapForum();
  app.boot();
  (app as any).mentionFormats = new MentionFormats();
  addComposerAutocomplete();
  // Only user mentions are exercised: posts and groups need a discussion and a signed-in user.
  for (const Mentionable of [PostMention, GroupMention]) {
    jest.spyOn(Mentionable.prototype, 'enabled').mockReturnValue(false);
    jest.spyOn(Mentionable.prototype, 'search').mockResolvedValue([]);
  }
  // The extension extends TextEditor by path, which applies once its chunk registers it.
  flarum.reg.add('core', 'common/components/TextEditor', TextEditor);
});

beforeEach(async () => {
  search = jest.spyOn(UserMention.prototype, 'search').mockImplementation(() => new Promise((resolve) => (resolveSearch = resolve)));

  root = document.createElement('div');
  document.body.append(root);

  const built = new Promise<void>((resolve) => {
    m.mount(root, {
      view: () => m(TextEditor, { composer: { editor: null }, value: '', onchange: () => {}, onsubmit: () => {}, onTextEditorBuilt: resolve }),
    });
  });

  await built;
  textarea = root.querySelector('textarea')!;
  // Let any throttle call left by the previous test's editor run out.
  await wait(AFTER_THROTTLE);
  search.mockClear();
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
  search.mockRestore();
});

test('shows users found by the search while the mention is still being typed', async () => {
  type('Hi @ad');
  resolveSearch([adrian()]);
  await wait(0);

  expect(dropdownText()).toContain('adrian');
});

test('ignores search results that arrive after the mention was removed', async () => {
  type('Hi @ad');
  type('Hi ');
  resolveSearch([adrian()]);
  await wait(AFTER_THROTTLE);

  expect(dropdownText()).toBeNull();
});

test('does not search again for a mention removed before the throttle ran', async () => {
  type('@a');
  type('@ad');
  type('');
  await wait(AFTER_THROTTLE);

  expect(search.mock.calls.map(([typed]) => typed)).not.toContain('ad');
  expect(dropdownText()).toBeNull();
});

test('ignores search results for a mention the user has since changed', async () => {
  type('Hi @ad');
  const staleSearch = resolveSearch;
  type('Hi @adz');
  staleSearch([adrian()]);
  await wait(0);

  expect(dropdownText()).toBeNull();
});

test('ignores search results for the same query typed at another position', async () => {
  type('Hi @ad');
  const staleSearch = resolveSearch;
  type('Hi @ad @ad');
  staleSearch([adrian()]);
  await wait(0);

  // The store's admin user still matches locally.
  expect(dropdownText()).not.toContain('adrian');
});

test('ignores search results that arrive after the user selected text', async () => {
  type('Hi @ad');
  textarea.setSelectionRange(0, 2);
  textarea.dispatchEvent(new MouseEvent('click'));
  resolveSearch([adrian()]);
  await wait(AFTER_THROTTLE);

  expect(dropdownText()).toBeNull();
});

test('ignores search results that arrive after the editor is removed', async () => {
  type('Hi @ad');
  const staleSearch = resolveSearch;
  const dropdownContainer = root.querySelector('.ComposerBody-mentionsDropdownContainer')!;
  m.mount(root, null);
  staleSearch([adrian()]);
  await wait(AFTER_THROTTLE);

  expect(dropdownContainer.textContent).not.toContain('adrian');
});

test('ignores search results for a mention the user has since left for another', async () => {
  type('Hi @ad');
  const staleSearch = resolveSearch;
  type('Hi @ad @xy');
  staleSearch([adrian()]);
  await wait(0);

  expect(dropdownText()).toBeNull();
});
