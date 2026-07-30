import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { jest } from '@jest/globals';

import addTagFilter from '../../../src/forum/addTagFilter';
import Tag from '../../../src/common/models/Tag';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

/**
 * The slug the current route would expose via m.route.param('tags'), as
 * surfaced through app.search.state.params(). Tests drive navigation by
 * changing this value — app.currentTag() must stay consistent with it.
 */
let routeTagsParam: string | undefined;

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  (app.store.models as any).tags = Tag;

  app.store.pushPayload({
    data: [
      {
        id: '1',
        type: 'tags',
        attributes: { slug: 'general', name: 'General' },
        relationships: { children: { data: [] } },
      },
      {
        id: '2',
        type: 'tags',
        attributes: { slug: 'bugs', name: 'Bugs' },
        relationships: { children: { data: [] } },
      },
    ],
  });

  addTagFilter();

  (app as any).search = { state: { params: () => ({ tags: routeTagsParam }) } };
});

beforeEach(() => {
  routeTagsParam = undefined;
  (app as any).currentActiveTag = undefined;
  (app as any).currentTagLoading = false;
});

describe('app.currentTag()', () => {
  it('resolves the tag for the current route and caches it', () => {
    routeTagsParam = 'general';

    const tag = app.currentTag();

    expect(tag).toBeDefined();
    expect(tag!.slug()).toBe('general');
    expect((app as any).currentActiveTag).toBe(tag);
  });

  it('returns the cached tag for the same slug without refetching', () => {
    routeTagsParam = 'general';

    const first = app.currentTag();

    const find = jest.spyOn(app.store, 'find');
    const second = app.currentTag();
    find.mockRestore();

    expect(second).toBe(first);
    expect(find).not.toHaveBeenCalled();
  });

  it('matches the cached tag against the slug case-insensitively', () => {
    routeTagsParam = 'general';
    const first = app.currentTag();

    routeTagsParam = 'General';

    expect(app.currentTag()).toBe(first);
  });

  it('returns undefined on a route with no tags param, even when a tag was cached', () => {
    // Visit a tag page…
    routeTagsParam = 'general';
    expect(app.currentTag()).toBeDefined();

    // …then navigate to a route with no tags param (e.g. a custom homepage).
    // The cached tag no longer matches the route and must not leak into it:
    // consumers such as the composer's tag preselection and the sidebar's
    // "Start a Discussion" gating read this on every route.
    routeTagsParam = undefined;

    expect(app.currentTag()).toBeUndefined();
    expect((app as any).currentActiveTag).toBeUndefined();
  });

  it('re-resolves when the route points at a different tag than the cached one', () => {
    routeTagsParam = 'general';
    expect(app.currentTag()!.slug()).toBe('general');

    routeTagsParam = 'bugs';

    expect(app.currentTag()!.slug()).toBe('bugs');
  });
});
