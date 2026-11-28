import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import TextEditor from 'flarum/common/components/TextEditor';
import m from 'mithril';
import addComposerAutocomplete from '../../../src/forum/addComposerAutocomplete';
import emojiMap from '../../../src/forum/emojiMap';

let root: HTMLElement;
let textarea: HTMLTextAreaElement;

// BasicEditorDriver runs the input listeners on both `input` and `keyup` for each keystroke.
function type(value: string) {
  textarea.value = value;
  textarea.setSelectionRange(value.length, value.length);
  textarea.dispatchEvent(new Event('input'));
  textarea.dispatchEvent(new KeyboardEvent('keyup'));
}

const ESCAPE = { key: 'Escape', keyCode: 27, which: 27, bubbles: true, cancelable: true } as KeyboardEventInit;

// The composer handles Escape on an ancestor of the editor.
function pressEscape(): boolean {
  let reachedComposer = false;
  const listener = () => (reachedComposer = true);
  root.addEventListener('keydown', listener);
  textarea.dispatchEvent(new KeyboardEvent('keydown', ESCAPE));
  textarea.dispatchEvent(new KeyboardEvent('keyup', ESCAPE));
  root.removeEventListener('keydown', listener);

  return reachedComposer;
}

function moveCaretTo(position: number) {
  textarea.setSelectionRange(position, position);
  textarea.dispatchEvent(new MouseEvent('click'));
}

function dropdownVisible(): boolean {
  const dropdown = root.querySelector<HTMLElement>('.EmojiDropdown');

  return !!dropdown && dropdown.style.display !== 'none';
}

beforeAll(() => {
  // simple-emoji-map also exports `variants`, which the webpack bundle leaves out but Jest's CommonJS interop keeps.
  delete (emojiMap as any).variants;
  bootstrapForum();
  app.store.pushPayload({
    data: { type: 'forums', id: '1', attributes: { 'flarum-emoji.cdn': 'https://example.com/twemoji/[version]/' } },
  } as any);
  app.forum = app.store.getById('forums', '1')!;
  addComposerAutocomplete();
  // The extension extends TextEditor by path, which applies once its chunk registers it.
  flarum.reg.add('core', 'common/components/TextEditor', TextEditor);
});

beforeEach(async () => {
  root = document.createElement('div');
  document.body.append(root);

  const built = new Promise<void>((resolve) => {
    m.mount(root, {
      view: () => m(TextEditor, { composer: { editor: null }, value: '', onchange: () => {}, onsubmit: () => {}, onTextEditorBuilt: resolve }),
    });
  });

  await built;
  textarea = root.querySelector('textarea')!;
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
});

test('shows suggestions while an emoji is being typed', () => {
  type('Hi :smi');

  expect(dropdownVisible()).toBe(true);
});

test('Escape closes the dropdown, and keyup does not reopen it', () => {
  type('Hi :smi');

  expect(pressEscape()).toBe(false);
  expect(dropdownVisible()).toBe(false);
});

test('a second Escape is left for the composer', () => {
  type('Hi :smi');
  pressEscape();

  expect(pressEscape()).toBe(true);
});

test('keeps the dropdown closed when the caret moves within the dismissed emoji', () => {
  type('Hi :smi');
  pressEscape();
  moveCaretTo('Hi :sm'.length);

  expect(dropdownVisible()).toBe(false);
});

test('keeps the dropdown closed when the caret leaves the dismissed emoji and comes back', () => {
  type('Hi :smi');
  pressEscape();
  moveCaretTo(1);
  moveCaretTo('Hi :smi'.length);

  expect(dropdownVisible()).toBe(false);
});

test('opens the dropdown for another emoji after one is dismissed', () => {
  type('Hi :smi :smi');
  pressEscape();
  moveCaretTo('Hi :smi'.length);

  expect(dropdownVisible()).toBe(true);
});

test('keeps every dismissed emoji closed until the text changes', () => {
  type('Hi :smi :smi');
  pressEscape();
  moveCaretTo('Hi :smi'.length);
  pressEscape();
  moveCaretTo('Hi :smi :smi'.length);
  expect(dropdownVisible()).toBe(false);

  moveCaretTo('Hi :smi'.length);
  expect(dropdownVisible()).toBe(false);
});

test('leaves Escape for the composer when no emoji matches', () => {
  type('Hi :smiqqqqq');

  expect(dropdownVisible()).toBe(false);
  expect(pressEscape()).toBe(true);
});

test('Escape with no matching emoji does not dismiss the emoji', () => {
  type('Hi :smiqqqqq');
  pressEscape();
  moveCaretTo('Hi :smi'.length);

  expect(dropdownVisible()).toBe(true);
});

// Undo can restore a selection along with the text.
function typeSelected(value: string, start: number, end: number) {
  textarea.value = value;
  textarea.setSelectionRange(start, end);
  textarea.dispatchEvent(new Event('input'));
}

test('reopens the dismissed emoji after text changes that leave a selection', () => {
  type('Hi :smi');
  pressEscape();
  typeSelected('Bye :smi', 0, 3);
  typeSelected('Hi :smi', 0, 2);
  moveCaretTo('Hi :smi'.length);

  expect(dropdownVisible()).toBe(true);
});

test('closes the dropdown when an edit leaves a selection, and leaves Escape for the composer', () => {
  type('Hi :smil');
  typeSelected('Hi :smiqqqqq', 'Hi :smi'.length, 'Hi :smiqqqqq'.length);

  expect(dropdownVisible()).toBe(false);
  expect(pressEscape()).toBe(true);

  moveCaretTo('Hi :smi'.length);
  expect(dropdownVisible()).toBe(true);
});

test('reopens the dropdown once the dismissed emoji changes', () => {
  type('Hi :smi');
  pressEscape();
  type('Hi :smil');

  expect(dropdownVisible()).toBe(true);
});
