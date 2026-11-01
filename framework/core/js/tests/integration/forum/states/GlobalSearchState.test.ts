import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import GlobalSearchState from '../../../../src/forum/states/GlobalSearchState';
import app from '../../../../src/forum/app';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

/**
 * A search arrives in the address from outside the forum: a browser's
 * address bar sends `?q=hello+world`. The router reads the address the way
 * Mithril parses a query string, which keeps the "+" and turns "true" and
 * "false" into booleans.
 */
describe('GlobalSearchState reads the search from the address', () => {
  const originalRouteParam = m.route.param;

  const visit = (search: string) => {
    window.history.replaceState(null, '', `/${search}`);
    m.route.param = ((key?: string) => {
      const params = m.parseQueryString(window.location.search);

      return key ? params[key] : params;
    }) as typeof m.route.param;
  };

  afterEach(() => {
    m.route.param = originalRouteParam;
    window.history.replaceState(null, '', '/');
  });

  test('a "+" is a space', () => {
    visit('?q=hello+world');

    expect(new GlobalSearchState().params().q).toBe('hello world');
  });

  test('an encoded "+" is a plus', () => {
    visit('?q=c%2B%2B');

    expect(new GlobalSearchState().params().q).toBe('c++');
  });

  test.each(['true', 'false'])('a search for "%s" is text', (word) => {
    visit(`?q=${word}`);

    expect(new GlobalSearchState().params().q).toBe(word);
  });

  test('no search is none', () => {
    visit('?sort=newest');

    expect(new GlobalSearchState().params().q).toBeUndefined();
  });
});
