import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import AuditBrowser from '../../../../src/common/components/AuditBrowser';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../../framework/core/js');

const keyCodes: Record<string, number> = { Enter: 13, Escape: 27, ArrowUp: 38, ArrowDown: 40 };

let requests: any[];
let requestOptions: any[];
let root: HTMLElement;

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum({
      resources: [
        {
          type: 'forums',
          id: '1',
          attributes: {
            auditFilters: [
              { key: 'actor', example: 'actor:admin', description: null, values: [], extension: null },
              { key: 'client', example: 'client:', description: null, values: ['session', 'api_key', 'cli'], extension: null },
            ],
            auditActions: { core: ['user.created', 'user.deleted', 'user.renamed'] },
          },
        },
      ],
    });
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  // jsdom has no layout.
  Element.prototype.scrollIntoView = () => {};
});

beforeEach(() => {
  requests = [];
  requestOptions = [];
  app.store.find = ((type: string, params: any, query: undefined, options: any) => {
    requests.push(params.filter);
    requestOptions.push(options);
    return Promise.resolve(Object.assign([], { payload: { links: {} } }));
  }) as any;

  root = document.createElement('div');
  document.body.appendChild(root);
  m.mount(root, { view: () => m(AuditBrowser) });
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
});

const input = () => root.querySelector<HTMLInputElement>('.AuditSearch .FormControl')!;
const suggestions = () => Array.from(root.querySelectorAll<HTMLElement>('.AuditAutocomplete-item')).map((el) => el.textContent);
const highlighted = () => root.querySelector('.AuditAutocomplete-item.active')?.textContent ?? null;

function type(value: string) {
  const el = input();
  el.focus();
  el.dispatchEvent(new FocusEvent('focus'));
  el.value = value;
  el.setSelectionRange(value.length, value.length);
  el.dispatchEvent(new Event('input', { bubbles: true }));
  m.redraw.sync();
}

function press(key: string, init: KeyboardEventInit = {}) {
  const options = { key, which: keyCodes[key], keyCode: keyCodes[key], bubbles: true, cancelable: true, ...init } as KeyboardEventInit;
  const down = new KeyboardEvent('keydown', options);
  const up = new KeyboardEvent('keyup', options);

  input().dispatchEvent(down);
  input().dispatchEvent(up);
  m.redraw.sync();

  return { down, up };
}

describe('AuditBrowser filter box', () => {
  it('applies the typed filter on Enter', () => {
    type('actor:admin');
    requests = [];

    press('Enter');

    expect(requests).toEqual([{ actor: 'admin' }]);
  });

  it('ignores a held Enter', () => {
    type('actor:admin');
    press('Enter');
    requests = [];

    press('Enter', { repeat: true });

    expect(requests).toEqual([]);
  });

  it('leaves Enter alone while an IME is composing', () => {
    type('actor:admin');
    requests = [];

    press('Enter', { isComposing: true });

    expect(requests).toEqual([]);
  });

  it('picks the highlighted suggestion on Enter, without applying the filter', () => {
    type('client:');
    expect(suggestions()).toEqual(['session', 'api_key', 'cli']);
    requests = [];

    press('ArrowDown');
    press('ArrowDown');
    expect(highlighted()).toBe('api_key');

    press('Enter');

    expect(input().value).toBe('client:api_key');
    expect(requests).toEqual([]);
  });

  it('applies the filter on Enter when no suggestion is highlighted, and closes the suggestions', () => {
    type('client:');
    requests = [];

    press('Enter');

    expect(requests).toEqual([{ client: '' }]);
    expect(suggestions()).toEqual([]);
  });

  it('starts at the last suggestion when going up first', () => {
    type('client:');

    press('ArrowUp');

    expect(highlighted()).toBe('cli');
  });

  it('leaves modified arrow keys to the text field', () => {
    type('client:');

    const { down } = press('ArrowDown', { shiftKey: true });

    expect(down.defaultPrevented).toBe(false);
    expect(highlighted()).toBeNull();
  });

  it('drops the highlight when the caret moves to another filter', () => {
    type('client:s action:user.');
    input().setSelectionRange(8, 8);
    input().dispatchEvent(new MouseEvent('click', { bubbles: true }));
    m.redraw.sync();
    press('ArrowDown');
    expect(highlighted()).toBe('session');

    input().setSelectionRange(input().value.length, input().value.length);
    input().dispatchEvent(new MouseEvent('click', { bubbles: true }));
    m.redraw.sync();

    expect(highlighted()).toBeNull();
  });

  it('closes the suggestions on Escape, then clears the filter', () => {
    type('client:');
    requests = [];

    press('Escape');
    expect(suggestions()).toEqual([]);
    expect(input().value).toBe('client:');
    expect(requests).toEqual([]);

    press('Escape');
    expect(input().value).toBe('');
    expect(requests).toEqual([{}]);
  });

  it('keeps an Escape it used from reaching a surrounding modal', () => {
    const bodyKeyup = jest.fn();
    document.body.addEventListener('keyup', bodyKeyup);
    type('actor:admin');

    const { down } = press('Escape');

    document.body.removeEventListener('keyup', bodyKeyup);
    // A cancelled keydown stops CloseWatcher; the keyup is ModalManager's fallback.
    expect(down.defaultPrevented).toBe(true);
    expect(bodyKeyup).not.toHaveBeenCalled();
  });

  it('lets Escape in an empty box reach a surrounding modal', () => {
    const bodyKeyup = jest.fn();
    document.body.addEventListener('keyup', bodyKeyup);
    type('');

    const { down } = press('Escape');

    document.body.removeEventListener('keyup', bodyKeyup);
    expect(down.defaultPrevented).toBe(false);
    expect(bodyKeyup).toHaveBeenCalled();
  });

  it('does not step a held Escape through to the surrounding modal', () => {
    const bodyKeyup = jest.fn();
    document.body.addEventListener('keyup', bodyKeyup);
    type('client:');

    const options = { key: 'Escape', which: 27, keyCode: 27, bubbles: true, cancelable: true } as KeyboardEventInit;
    const repeats = [
      new KeyboardEvent('keydown', options),
      new KeyboardEvent('keydown', { ...options, repeat: true }),
      new KeyboardEvent('keydown', { ...options, repeat: true }),
    ];
    repeats.forEach((event) => input().dispatchEvent(event));
    input().dispatchEvent(new KeyboardEvent('keyup', options));
    m.redraw.sync();

    document.body.removeEventListener('keyup', bodyKeyup);
    expect(input().value).toBe('client:');
    expect(repeats.every((event) => event.defaultPrevented)).toBe(true);
    expect(bodyKeyup).not.toHaveBeenCalled();
  });

  it('shows the latest query when an earlier response arrives last', async () => {
    const pending: ((logs: any[]) => void)[] = [];
    app.store.find = (() => new Promise((done) => pending.push((logs) => done(Object.assign(logs, { payload: { links: {} } }))))) as any;
    const log = (action: string) => ({
      action: () => action,
      actorId: () => null,
      actor: () => null,
      client: () => 'cli',
      createdAt: () => new Date(),
      ipAddress: () => null,
      payload: () => ({}),
      discussion: () => null,
      newDiscussion: () => null,
      post: () => null,
      tag: () => null,
      user: () => null,
    });

    type('actor:admin');
    press('Enter');
    press('Escape');
    const [filtered, cleared] = pending;
    cleared([log('user.created')]);
    filtered([log('user.deleted'), log('user.renamed')]);
    await new Promise((done) => setTimeout(done));
    m.redraw.sync();

    expect(input().value).toBe('');
    expect(root.querySelectorAll('.AuditItem').length).toBe(1);
  });

  it('keeps the caret after the completed filter', () => {
    type('client:s actor:admin');
    input().setSelectionRange(8, 8);
    input().dispatchEvent(new MouseEvent('click', { bubbles: true }));
    m.redraw.sync();

    press('ArrowDown');
    press('Enter');

    expect(input().value).toBe('client:session actor:admin');
    expect(input().selectionStart).toBe('client:session'.length);
  });

  it('highlights the suggestion the pointer moves over, for Enter to pick', () => {
    type('client:');

    root.querySelectorAll('.AuditAutocomplete-item')[2].dispatchEvent(new MouseEvent('mousemove', { bubbles: true }));
    m.redraw.sync();
    expect(highlighted()).toBe('cli');

    press('Enter');
    expect(input().value).toBe('client:cli');
  });

  it('keeps suggestions out of the Tab order', () => {
    type('client:');

    expect(Array.from(root.querySelectorAll('.AuditAutocomplete-item')).every((el) => el.getAttribute('tabindex') === '-1')).toBe(true);
  });

  it('labels each group of actions with its extension', () => {
    type('action:user.');

    const group = root.querySelector('[role="group"]')!;
    expect(document.getElementById(group.getAttribute('aria-labelledby')!)?.textContent).toBe('core');
    expect(group.querySelectorAll('[role="option"]').length).toBe(3);
  });

  it('keeps an Escape it used from the modal when another key is pressed before letting go', () => {
    const bodyKeyup = jest.fn();
    document.body.addEventListener('keyup', bodyKeyup);
    type('actor:admin');

    const escape = { key: 'Escape', which: 27, keyCode: 27, bubbles: true, cancelable: true } as KeyboardEventInit;
    input().dispatchEvent(new KeyboardEvent('keydown', escape));
    input().dispatchEvent(new KeyboardEvent('keydown', { key: 'Shift', which: 16, keyCode: 16, bubbles: true, cancelable: true }));
    input().dispatchEvent(new KeyboardEvent('keyup', escape));

    document.body.removeEventListener('keyup', bodyKeyup);
    expect(bodyKeyup).not.toHaveBeenCalledWith(expect.objectContaining({ key: 'Escape' }));
  });

  it('shows core\'s error only for the latest request', () => {
    type('actor:admin');
    press('Enter');
    press('Escape');

    const [filtered, cleared] = requestOptions.slice(-2);
    // false hands the error to core's default alert.
    expect(cleared.errorHandler()).toBe(false);
    expect(filtered.errorHandler()).toBeUndefined();
  });

  it('drops a page loaded for an earlier query', async () => {
    const pending: ((logs: any[]) => void)[] = [];
    app.store.find = (() => new Promise((done) => pending.push((logs) => done(Object.assign(logs, { payload: { links: {} } }))))) as any;
    let browser: any;
    m.mount(root, { view: () => m(AuditBrowser, { oninit: (vnode: any) => (browser = vnode.state) }) });

    pending.shift()!([]);
    await new Promise((done) => setTimeout(done));
    browser.loadMore();
    browser.refresh();
    const [page, fresh] = pending;
    fresh([]);
    page([{ id: () => 'stale' }]);
    await new Promise((done) => setTimeout(done));

    expect(browser.logs).toEqual([]);
  });

  it('points the combobox at the highlighted option', () => {
    type('client:');
    expect(input().getAttribute('aria-expanded')).toBe('true');

    press('ArrowDown');

    const active = root.querySelector('.AuditAutocomplete-item.active')!;
    expect(input().getAttribute('aria-activedescendant')).toBe(active.id);
    expect(active.getAttribute('aria-selected')).toBe('true');
  });
});
