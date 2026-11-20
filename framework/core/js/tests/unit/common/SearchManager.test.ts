import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../src/forum/app';
import SearchManager from '../../../src/common/SearchManager';
import Search from '../../../src/forum/components/Search';

class ShortSearch extends Search {
  protected static MIN_SEARCH_LEN = 1;
}

class ShorterSearch extends ShortSearch {}

/**
 * The minimum query length is a frontend gate deciding when a search actually
 * fires. Admins configure it (`search_min_length`, default 3), and the forum
 * reads it from the forum resource, since the forum frontend has no access to
 * raw settings.
 */
beforeAll(() => {
  bootstrapForum();
  app.boot();
});

function setForum(attributes: Record<string, unknown> | undefined): void {
  app.forum = attributes === undefined ? (undefined as any) : app.store.createRecord('forums', { attributes });
}

describe('SearchManager min search length', () => {
  it('uses the configured minimum from the forum resource', () => {
    setForum({ searchMinLength: 2 });

    expect(SearchManager.minSearchLength()).toBe(2);
    expect(Search.minSearchLength()).toBe(2);
  });

  it('allows a minimum of 1', () => {
    setForum({ searchMinLength: 1 });

    expect(SearchManager.minSearchLength()).toBe(1);
    expect(Search.minSearchLength()).toBe(1);
  });

  it('falls back to the standard minimum when the forum does not provide one', () => {
    setForum({});

    expect(SearchManager.minSearchLength()).toBe(SearchManager.MIN_SEARCH_LEN);
    expect(SearchManager.minSearchLength()).toBe(3);
    expect(Search.minSearchLength()).toBe(3);
  });

  it('falls back to the standard minimum before the forum is loaded', () => {
    setForum(undefined);

    expect(SearchManager.minSearchLength()).toBe(3);
    expect(Search.minSearchLength()).toBe(3);
  });

  it('keeps a minimum that a Search subclass sets itself', () => {
    setForum({ searchMinLength: 3 });

    expect(ShortSearch.minSearchLength()).toBe(1);
    expect(ShorterSearch.minSearchLength()).toBe(1);
  });

  it('does not let CJK mode override the configured minimum', () => {
    setForum({ searchMinLength: 3, searchCjkMode: true });

    expect(SearchManager.minSearchLength()).toBe(3);
    expect(Search.minSearchLength()).toBe(3);
  });
});

describe('SearchManager query length', () => {
  it('counts characters outside the Basic Multilingual Plane once', () => {
    expect(SearchManager.queryLength('中')).toBe(1);
    expect(SearchManager.queryLength('𠮷')).toBe(1);
    expect(SearchManager.queryLength('𠮷野家')).toBe(3);
    expect(SearchManager.queryLength('abc')).toBe(3);
  });

  it('does not count surrounding whitespace', () => {
    expect(SearchManager.queryLength(' ')).toBe(0);
    expect(SearchManager.queryLength('  中 ')).toBe(1);
    expect(SearchManager.queryLength('a b')).toBe(3);
  });
});

describe('SearchManager CJK mode', () => {
  it('reads CJK mode from the forum resource', () => {
    setForum({ searchCjkMode: true });
    expect(SearchManager.isCjkMode()).toBe(true);

    setForum({ searchCjkMode: false });
    expect(SearchManager.isCjkMode()).toBe(false);
  });

  it('is off before the forum is loaded', () => {
    setForum(undefined);

    expect(SearchManager.isCjkMode()).toBe(false);
  });
});
