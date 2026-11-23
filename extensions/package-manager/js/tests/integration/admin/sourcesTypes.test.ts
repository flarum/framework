import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from 'flarum/admin';
import mq from 'mithril-query';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import SourcesTab from '../../../src/admin/components/SourcesTab';

// Fixtures hold types and kinds the Sources form cannot add but composer.json and auth.json can (flarum/framework#5585).

let answer: any = null;

const flush = () => new Promise((r) => setTimeout(r, 0));

beforeAll(() => {
  bootstrapAdmin();
  app.boot();

  const locale = path.resolve(process.cwd(), '../locale/en.yml');
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(locale, 'utf8')) as object));

  (app as any).request = () => Promise.resolve(answer);
});

async function render(repositories: { type: string; url: string }[], stored: { kind: string; host: string; username: string | null }[] = []) {
  answer = {
    repositories,
    types: ['vcs', 'composer', 'path'],
    stability: { minimumStability: 'stable', preferStable: true },
    auth: { stored },
    note: '',
  };

  const tab = mq(SourcesTab);
  await flush();
  tab.redraw();

  return tab;
}

describe('SourcesTab repository types', () => {
  test('an artifact repository is named, and by its folder', async () => {
    const tab = await render([{ type: 'artifact', url: '/srv/zips/' }]);
    tab.should.not.contain('repositories.types.');
    tab.should.contain('Folder of package archives');
    tab.should.have('.ExtensionManager-src-tileName');
    expect(tab.find('.ExtensionManager-src-tileName')[0].textContent).toBe('zips');
    tab.should.have('.fa-file-zipper');
  });

  test('a type without a label shows its own name, with a neutral icon', async () => {
    const tab = await render([{ type: 'acme-plugin', url: 'https://repo.example.com/feed' }]);
    tab.should.not.contain('repositories.types.');
    expect(tab.find('.ExtensionManager-src-badge')[0].textContent).toBe('acme-plugin');
    tab.should.have('.fa-box');
  });

  test("a named version-control driver is shown like vcs", async () => {
    const tab = await render([{ type: 'github', url: 'https://github.com/acme/private-ext' }]);
    tab.should.not.contain('repositories.types.');
    tab.should.not.have('.ExtensionManager-src-tile .ExtensionManager-src-badge');
    expect(tab.find('.ExtensionManager-src-tileName')[0].textContent).toBe('private-ext');
    tab.should.contain('github.com');
  });

  test('a credential kind without a label shows its own name', async () => {
    const tab = await render(
      [],
      [
        { kind: 'gitlab-oauth', host: 'gitlab.example.com', username: null },
        { kind: 'github-oauth', host: 'github.com', username: null },
      ]
    );
    const badges = tab.find('.ExtensionManager-src-item .ExtensionManager-src-badge').map((b: Element) => b.textContent);

    tab.should.not.contain('auth_config.types.');
    expect(badges).toContain('gitlab-oauth');
    expect(badges).toContain('GitHub token');
  });
});
