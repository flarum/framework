import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from 'flarum/admin';
import mq from 'mithril-query';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import RunPanel from '../../../src/admin/components/RunPanel';

/*
 * The abandoned-package notice on the run panel (flarum/framework#3993): the
 * packages a run brings in that their authors abandoned, with each
 * replacement, and wording that stays true whether the run went on or stopped.
 */

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
      // Ended runs only: a live one draws a progress bar whose inline style
      // the test DOM's CSS parser cannot read. A finished run says the same.
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
  test('names each package and its replacement, and says the update went ahead', () => {
    const out = panel({ abandoned });
    const notice = out.find('.ExtensionManager-abandoned')[0];

    expect(notice.textContent).toContain('2 packages in this update are abandoned');
    expect(notice.textContent).toContain('The update goes ahead');
    expect(notice.textContent).toContain('acme/old-lib: its author suggests acme/new-lib');
    expect(notice.textContent).toContain('acme/gone: no replacement is named');
  });

  test('on a run that stopped, says it is not why it stopped', () => {
    for (const state of ['failed', 'rolled-back']) {
      const notice = panel({ state, error: 'boom', abandoned }).find('.ExtensionManager-abandoned')[0];

      expect(notice.textContent).toContain('That is not why it stopped');
      expect(notice.textContent).not.toContain('goes ahead');
    }
  });

  test('is absent when nothing is abandoned, and from a run recorded before the list existed', () => {
    panel({ abandoned: [] }).should.not.have('.ExtensionManager-abandoned');
    panel({}).should.not.have('.ExtensionManager-abandoned');
  });
});
