import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from 'flarum/admin';
import mq from 'mithril-query';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import dayjs from 'dayjs';
import CorePanel from '../../../src/admin/components/CorePanel';
import RunPanel from '../../../src/admin/components/RunPanel';
import DiscoverTab from '../../../src/admin/components/DiscoverTab';
import HistoryTab from '../../../src/admin/components/HistoryTab';
import ExtensionManagerPage from '../../../src/admin/components/ExtensionManagerPage';
import { alertOnce } from '../../../src/admin/errors';

/*
 * The fixes from flarum/framework#5357's frontend findings, each driven the
 * way the browser would: real components, core's app, and a request stub that
 * answers when the test says so.
 */

type Call = { opts: any; resolve: (v: any) => void; reject: (e: any) => void };
let calls: Call[] = [];
let shown: any[] = [];
let modals: any[] = [];
let dismissed: any[] = [];

/** A translated message as plain text: the translator returns an array when it has placeholders. */
const text = (v: any): string => (Array.isArray(v) ? v.flat(Infinity).join('') : String(v));

const flush = () => new Promise((r) => setTimeout(r, 0));

beforeAll(() => {
  bootstrapAdmin();
  // Core's admin bundle provides this global; HistoryTab uses it.
  (globalThis as any).dayjs = dayjs;
  app.boot();

  const locale = path.resolve(process.cwd(), '../locale/en.yml');
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(locale, 'utf8')) as object));
});

beforeEach(() => {
  calls = [];
  shown = [];
  modals = [];
  dismissed = [];

  (app as any).request = (opts: any) =>
    new Promise((resolve, reject) => {
      calls.push({
        opts,
        resolve,
        // Core runs the request's errorHandler before rejecting; so does this.
        reject: (e: any) => {
          if (opts.errorHandler) opts.errorHandler(e);
          reject(e);
        },
      });
    });
  (app.alerts as any).show = (...args: any[]) => {
    shown.push(args);
    return shown.length;
  };
  (app.alerts as any).dismiss = (key: any) => dismissed.push(key);
  (app.modal as any).show = (component: any, attrs: any) => modals.push(attrs);
});

function run(over: Record<string, unknown> = {}) {
  return {
    id: 'r1',
    state: 'running',
    phase: 'apply',
    items: ['acme/one'],
    index: 0,
    log: [],
    error: null,
    errorStep: null,
    packages: ['acme/one'],
    mode: 'update',
    migrations: [],
    ...over,
  };
}

describe('the core Update button', () => {
  test('waits for the pre-flight, then asks before starting', async () => {
    const begun: string[][] = [];
    const out = mq(CorePanel, { starting: false, checkedAt: 1, onbegin: (p: string[]) => begun.push(p), onnightly: () => {} });

    calls[0].resolve({ current: '2.0.0', newest: { from: '2.0.0', to: '2.1.0' }, preflight: null });
    await flush();
    out.redraw();

    // The pre-flight has not answered: nothing is blocked only because nothing is checked.
    expect(out.find('.ExtensionManager-coreHead .Button--primary')[0].disabled).toBe(true);
    out.should.contain(app.translator.trans('flarum-extension-manager.admin.updater.core.preflight', { version: '2.1.0' }, true));

    calls[1].resolve({
      preflight: {
        target: '2.1.0',
        verdicts: [{ package: 'acme/one', installed: '1.0.0', state: 'needs-update', requires: null, to: '1.1.0' }],
        pending: [],
        blocked: 0,
        updating: 1,
      },
    });
    await flush();
    out.redraw();

    const button = out.find('.ExtensionManager-coreHead .Button--primary')[0];
    expect(button.disabled).toBe(false);

    button.click();

    expect(begun).toEqual([]);
    expect(modals).toHaveLength(1);
    expect(text(modals[0].message)).toContain('Update Flarum from 2.0.0 to 2.1.0? 1 extension listed below is updated with it');

    modals[0].onconfirm();
    expect(begun).toEqual([['flarum/core', 'acme/one']]);
  });

  test('stays disabled when the pre-flight fails to load', async () => {
    const out = mq(CorePanel, { starting: false, checkedAt: 1, onbegin: () => {}, onnightly: () => {} });

    calls[0].resolve({ current: '2.0.0', newest: { from: '2.0.0', to: '2.1.0' }, preflight: null });
    await flush();
    calls[1].reject({ status: 500 });
    await flush();
    out.redraw();

    expect(out.find('.ExtensionManager-coreHead .Button--primary')[0].disabled).toBe(true);
  });
});

describe('the run panel', () => {
  test('asks before undoing a failed run, and says how many database changes go back', () => {
    const out = mq(RunPanel, {
      run: run({ state: 'failed', error: 'boom', migrations: ['a', 'b'] }),
      driver: null,
      busy: false,
      stale: false,
      rollbackNote: null,
      onprogress: () => {},
      ondone: () => {},
      onrollback: () => {},
      ondismiss: () => {},
    });

    out.click('.ExtensionManager-error .Button');

    expect(calls.filter((c) => c.opts.url.endsWith('/rollback'))).toHaveLength(0);
    expect(modals).toHaveLength(1);
    expect(text(modals[0].message)).toContain('2 changes');
  });

  test('puts a failure in an alert region and progress in one polite status region', () => {
    const failed = mq(RunPanel, {
      run: run({ state: 'failed', error: 'boom', errorPhase: 'apply', errorItem: 'acme/one' }),
      driver: null,
      busy: false,
      stale: false,
      rollbackNote: null,
      onprogress: () => {},
      ondone: () => {},
      onrollback: () => {},
      ondismiss: () => {},
    });

    failed.should.have('.ExtensionManager-errorWhere[role="alert"]');
    expect(failed.find('[role="status"]')[0].textContent).toBe('');
  });

  test('a run of failed polls adds one alert, and a poll that lands takes it away', async () => {
    const panel: any = new (RunPanel as any)();
    panel.attrs = { run: run(), onprogress: () => {}, ondone: () => {} };
    panel.polling = true;

    panel.tick();
    for (let i = 0; i < 3; i++) {
      // The drive fails, then the read-only watch fails too.
      calls.shift()!.reject({ status: 502, alert: { type: 'error', content: 'core says no' } });
      await flush();
      calls.shift()!.reject({ status: 502, alert: { type: 'error', content: 'core says no' } });
      await flush();
      panel.tick();
    }

    expect(shown).toHaveLength(1);
    expect(shown[0][1]).toBe('core says no');

    calls.shift()!.resolve({ run: run(), idle: false });
    await flush();

    expect(dismissed).toEqual([1]);
    panel.polling = false;
  });

  test('alertOnce shows again only after clear()', () => {
    const once = alertOnce();
    const e: any = { alert: { type: 'error', content: 'x' } };

    once.handler(e);
    once.handler(e);
    once.clear();
    once.handler(e);

    expect(shown).toHaveLength(2);
  });
});

describe('Discover', () => {
  test('"Show more" pages through the searched query, and a new search clears its Loading…', async () => {
    const tab: any = new (DiscoverTab as any)();
    tab.attrs = { starting: false, oninstall: () => {} };

    tab.query = 'seo';
    tab.search();
    calls.shift()!.resolve({ results: [{ name: 'acme/seo' }], more: true });
    await flush();
    calls.shift(); // the compatibility pass

    // Typed but not searched yet: the next page must still be the one for "seo".
    tab.query = 'seo tools';
    tab.showMore();
    expect(calls[0].opts.url).toContain('q=seo&');
    expect(tab.loadingMore).toBe(true);

    const stalePage = calls.shift()!;
    tab.search();
    expect(tab.loadingMore).toBe(false);

    stalePage.resolve({ results: [{ name: 'acme/old-page-2' }], more: true });
    await flush();

    expect(tab.loadingMore).toBe(false);
    expect(tab.results.map((r: any) => r.name)).toEqual(['acme/seo']);
  });

  test('the search box has a label and the categories are one Tab stop', () => {
    const out = mq(DiscoverTab, { starting: false, oninstall: () => {} });

    expect(out.find('input[type="search"]')[0].getAttribute('aria-label')).toBe(
      app.translator.trans('flarum-extension-manager.admin.sections.discover.search', {}, true)
    );
    expect(out.find('[role="radio"]').map((b: HTMLElement) => b.getAttribute('tabindex'))).toEqual(['0', '-1', '-1', '-1']);
  });
});

describe('History', () => {
  test('offers Undo on the entry that can still be undone, and nowhere else', () => {
    const undone: number[] = [];
    const out = mq(HistoryTab, {
      history: [
        { id: 'r2', at: 1, state: 'failed', mode: 'update' },
        { id: 'r1', at: 0, state: 'done', mode: 'update' },
      ],
      installed: [],
      undoable: 'r2',
      rollingBack: false,
      onundo: () => undone.push(1),
    });

    const buttons = out.find('.ExtensionManager-historyItem .Button');
    expect(buttons).toHaveLength(1);

    buttons[0].click();
    expect(undone).toEqual([1]);
  });

  test('a dismissed failed run is offered for undo from History, through the same confirmation', () => {
    const page: any = new (ExtensionManagerPage as any)();
    page.run = run({ state: 'failed', migrations: ['m'] });
    page.dismissed = true;

    expect(page.undoableFromHistory()).toBe('r1');

    page.rollBackFromHistory();
    expect(modals).toHaveLength(1);
    expect(text(modals[0].message)).toContain('1 change)');

    // While the panel is on screen it has the button, so History does not.
    page.dismissed = false;
    expect(page.undoableFromHistory()).toBeNull();
  });
});

describe('the page', () => {
  test('a /state reply that left before Update was pressed does not take the new run away', async () => {
    const page: any = new (ExtensionManagerPage as any)();

    page.load();
    const late = calls.shift()!;

    page.start(['acme/one']);
    calls.shift()!.resolve({ run: run({ id: 'new', state: 'pending' }), driver: 'browser' });
    await flush();

    late.resolve({ host: {}, installed: [], history: [], run: null, runIsStale: false });
    await flush();

    expect(page.run.id).toBe('new');
  });

  test('overlapping loads: the newest reply wins whatever order they land in', async () => {
    const page: any = new (ExtensionManagerPage as any)();

    page.load();
    page.load();
    const [older, newer] = calls;

    newer.resolve({ host: {}, installed: [], history: [], run: run({ id: 'newer', state: 'done' }), runIsStale: false });
    await flush();
    older.resolve({ host: {}, installed: [], history: [], run: null, runIsStale: false });
    await flush();

    expect(page.run.id).toBe('newer');
  });
});
