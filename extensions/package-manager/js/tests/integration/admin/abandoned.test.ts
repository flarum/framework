import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from 'flarum/admin';
import mq from 'mithril-query';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import RunPanel from '../../../src/admin/components/RunPanel';
import { reloadAfter } from '../../../src/admin/runState';

beforeAll(() => {
  bootstrapAdmin();
  app.boot();

  const locale = path.resolve(process.cwd(), '../locale/en.yml');
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(locale, 'utf8')) as object));

  // The panel polls while a run is live; these tests only read what it draws.
  (app as any).request = () => new Promise(() => {});
});

function panel(over: Record<string, unknown>) {
  return mq(RunPanel, {
    run: {
      id: 'r1',
      // Ended runs only: the test DOM can't parse a live run's progress bar style.
      state: 'done',
      phase: 'finalise',
      items: [],
      index: 0,
      log: [],
      error: null,
      errorStep: null,
      packages: ['acme/widget'],
      mode: 'install',
      migrations: [],
      ...over,
    },
    driver: null,
    busy: false,
    stale: false,
    rollbackNote: null,
    onprogress: () => {},
    ondone: () => {},
    onrollback: () => {},
    ondismiss: () => {},
  });
}

const abandoned = [
  { package: 'acme/gone', replacement: null },
  { package: 'acme/old-lib', replacement: 'acme/new-lib' },
];

describe('the abandoned-package notice', () => {
  test('names each package and its replacement, and says install on an install', () => {
    const notice = panel({ abandoned }).find('.ExtensionManager-abandoned')[0];

    expect(notice.textContent).toContain('2 packages in this install are abandoned');
    expect(notice.textContent).toContain('acme/old-lib: use acme/new-lib instead');
    expect(notice.textContent).toContain('acme/gone: no replacement is named');
  });

  test('says update on an update', () => {
    expect(panel({ mode: 'update', abandoned }).find('.ExtensionManager-abandoned')[0].textContent).toContain(
      '2 packages in this update are abandoned'
    );
  });

  test('on a failed run, says it is not why it stopped', () => {
    const notice = panel({ state: 'failed', error: 'boom', abandoned }).find('.ExtensionManager-abandoned')[0];

    expect(notice.textContent).toContain('this install would bring in are abandoned');
    expect(notice.textContent).toContain('That is not why it stopped');
  });

  test('is gone once the run is undone, or put back after failing', () => {
    panel({ state: 'rolled-back', error: null, abandoned }).should.not.have('.ExtensionManager-abandoned');
    panel({ state: 'rolled-back', error: 'boom', abandoned }).should.not.have('.ExtensionManager-abandoned');
  });

  test('is absent when nothing is abandoned, and from a run recorded before the list existed', () => {
    panel({ abandoned: [] }).should.not.have('.ExtensionManager-abandoned');
    panel({}).should.not.have('.ExtensionManager-abandoned');
  });
});

/*
 * A single-package install reloads onto the new extension's page and dismisses
 * its run. Here the run finished between two polls, so the admin never saw it.
 */
describe('after a single-package install', () => {
  const finished = (over: Record<string, unknown> = {}) => ({ id: 'r5', state: 'done', mode: 'install', packages: ['acme/widget'], ...over });

  test('with abandoned packages, the page reloads onto the run panel instead of the extension', () => {
    expect(reloadAfter(finished({ abandoned }), 'flarum/extension-manager')).toEqual({ reload: true, open: null });
  });

  test('without any, it still opens the new extension', () => {
    expect(reloadAfter(finished({ abandoned: [] }), 'flarum/extension-manager')).toEqual({ reload: true, open: 'acme/widget' });
    expect(reloadAfter(finished(), 'flarum/extension-manager')).toEqual({ reload: true, open: 'acme/widget' });
  });

  test('nothing reloads while the run is going, or for an ordinary update', () => {
    expect(reloadAfter(finished({ state: 'running', abandoned }), 'flarum/extension-manager').reload).toBe(false);
    expect(reloadAfter(finished({ mode: 'update', abandoned }), 'flarum/extension-manager').reload).toBe(false);
  });
});

describe('the live region', () => {
  const running = (over: Record<string, unknown> = {}) =>
    ({ id: 'r2', state: 'running', phase: 'fetch', items: ['fetch:acme/widget'], index: 0, log: [], packages: ['acme/widget'], ...over }) as any;

  test('announces the abandoned packages once, when the plan brings them, then only progress', () => {
    const live = Object.create(RunPanel.prototype);

    expect(live.announcement(running({ phase: 'plan', items: [] }))).not.toContain('abandoned');

    const first = live.announcement(running({ abandoned }));
    expect(first).toContain('2 packages in this update are abandoned');
    expect(first).toContain('The list is in the update panel');

    const next = live.announcement(running({ abandoned, index: 0 }));
    expect(next).not.toContain('abandoned');
    expect(next).not.toBe('');
  });

  test('announces a new run that has them, and not a run without any', () => {
    const live = Object.create(RunPanel.prototype);

    live.announcement(running({ abandoned }));
    expect(live.announcement(running({ id: 'r3', abandoned: [abandoned[0]] }))).toContain('A package in this update is abandoned');
    expect(live.announcement(running({ id: 'r4', abandoned: [] }))).not.toContain('abandoned');
  });
});
