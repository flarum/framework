import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { jest } from '@jest/globals';

import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';

import addTagFilter from '../../../src/forum/addTagFilter';
import Tag from '../../../src/common/models/Tag';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

/**
 * The slug the current route would expose via m.route.param('tags'), as
 * surfaced through app.search.state.params(). Tests drive navigation by
 * changing this value — app.currentTag() must stay consistent with it.
 */
let routeTagsParam: string | undefined;

/** What m.route.param() would return for the current route. */
let routeParams: Record<string, string | undefined> = {};

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
      {
        id: '3',
        type: 'tags',
        attributes: { slug: 'sorted', name: 'Sorted', defaultSort: 'az' },
        relationships: { children: { data: [] } },
      },
      {
        id: '4',
        type: 'tags',
        attributes: { slug: 'stale', name: 'Stale', defaultSort: 'sort_from_a_removed_extension' },
        relationships: { children: { data: [] } },
      },
    ],
  });

  addTagFilter();

  (app as any).search = { state: { params: () => ({ tags: routeTagsParam }) } };
});

beforeEach(() => {
  routeTagsParam = undefined;
  routeParams = {};
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

/**
 * A tag can name the order its page opens with.
 *
 * The server applies it when rendering the page, but the first client-side
 * request is built from the URL alone — so without this the list is fetched
 * again in the default order and replaces the one the reader arrived to.
 */
describe("a tag's default sort", () => {
  // Drive the real state object, so what is asserted is what the forum does.
  const params = () => new GlobalSearchState().params();

  beforeEach(() => {
    (m as any).route = { param: (key: string) => routeParams[key] };
  });

  it('is used when the reader has not asked for a sort', () => {
    routeParams = { tags: 'sorted' };

    expect(params().sort).toBe('az');
  });

  it('leaves a sort chosen by the reader alone', () => {
    routeParams = { tags: 'sorted', sort: 'newest' };

    expect(params().sort).toBe('newest');
  });

  it('is absent for a tag that has not set one', () => {
    routeParams = { tags: 'general' };

    expect(params().sort).toBeUndefined();
  });

  it('is ignored when the sort no longer exists', () => {
    // The extension that registered it may simply be disabled for now, and
    // passing it on would have the API reject the request.
    routeParams = { tags: 'stale' };

    expect(params().sort).toBeUndefined();
  });
});
