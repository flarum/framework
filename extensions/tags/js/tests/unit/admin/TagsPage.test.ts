import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import mq from 'mithril-query';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { jest } from '@jest/globals';

import TagsPage from '../../../src/admin/components/TagsPage';
import Tag from '../../../src/common/models/Tag';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapAdmin();
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  (app.store.models as any).tags = Tag;

  const tag = (id: string, name: string, position: number | null, parent: string | null = null) => ({
    id,
    type: 'tags',
    attributes: { slug: name.toLowerCase(), name, position, isChild: parent !== null },
    relationships: { parent: { data: parent ? { type: 'tags', id: parent } : null } },
  });

  app.store.pushPayload({
    data: [tag('1', 'General', 0), tag('2', 'Child', 0, '1'), tag('3', 'Alpha', null), tag('4', 'Bravo', null)],
  });
});

/**
 * Attach the drag-and-drop behaviour to the rendered tag tree, returning the
 * options each list was made sortable with, keyed by the tags it holds.
 */
function sortableOptions(): Record<string, any> {
  const page = Object.create(TagsPage.prototype);
  page.loading = false;
  page.forcedRefreshKey = 0;

  const root = mq({ view: () => page.content() }).rootEl as HTMLElement;
  const create = jest.fn();

  page.$ = (selector: string) => $(root).find(selector);
  page.sortable = { create };
  page.onListOnCreate();

  const names = (list: HTMLElement) =>
    Array.from(list.children)
      .map((li) => li.querySelector('.TagListItem-name')?.textContent)
      .join(',');

  return Object.fromEntries(create.mock.calls.map(([list, options]: any) => [names(list), options]));
}

/**
 * Secondary tags have no stored order: they are listed alphabetically, and
 * the order request only carries primary tags and their children. Their list
 * must not offer a reordering that would be discarded, but tags must still
 * move between the primary and secondary lists.
 */
describe('TagsPage tag lists', () => {
  it('can reorder primary tags and children', () => {
    const options = sortableOptions();

    expect(options['General'].sort).not.toBe(false);
    expect(options['Child'].sort).not.toBe(false);
  });

  it('cannot reorder secondary tags among themselves', () => {
    expect(sortableOptions()['Alpha,Bravo'].sort).toBe(false);
  });

  it('keeps every list in one group, so tags move between primary and secondary', () => {
    const options = sortableOptions();

    expect(Object.keys(options).sort()).toEqual(['Alpha,Bravo', 'Child', 'General']);

    const groups = Object.values(options).map((option) => option.group);

    expect(groups).toEqual(['tags', 'tags', 'tags']);
  });
});
