import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import { extend } from '../../../../src/common/extend';
import PostListState from '../../../../src/forum/states/PostListState';
import type { PaginatedListRequestParams } from '../../../../src/common/states/PaginatedListState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => jest.restoreAllMocks());

/** The query a list sends when it loads a page. */
async function querySentBy(state: PostListState, page: number = 1): Promise<Record<string, any>> {
  const find = jest.spyOn(app.store, 'find').mockResolvedValue(Object.assign([], { payload: { data: [] } }) as any);

  await (state as any).loadPage(page);

  return find.mock.calls[0][1] as any;
}

// Post lists never show how many posts match, and counting them can cost more
// than the page itself (a prolific author's posts), so they ask the API to
// skip the count. They still ask for the API's default includes.
describe('post lists', () => {
  test('ask the API not to count every match', () => {
    const params = new PostListState({}).requestParams();

    expect(params.page).toEqual({ total: 0 });
    expect(params.withDefaultIncludes).toBe(true);
  });

  test('send the opt-out with the page they load', async () => {
    const query = await querySentBy(new PostListState({ filter: { author: 'author' } }, 1, 20), 2);

    expect(query.page).toEqual({ total: 0, offset: 20, limit: 20 });
    expect(query.include).toBe('user,discussion');
    expect(query.withDefaultIncludes).toBe(true);
    expect(query.filter).toEqual({ type: 'comment', author: 'author' });
  });

  // fof/bookmarks: sends no include, so the endpoint's defaults apply.
  test('keep the opt-out in a subclass that drops the include', async () => {
    class WithoutInclude extends PostListState {
      requestParams(): PaginatedListRequestParams {
        const params = super.requestParams();

        delete params.include;

        return params;
      }
    }

    const query = await querySentBy(new WithoutInclude({ filter: { bookmarked: '1' } }, 1, 20));

    expect(query).not.toHaveProperty('include');
    expect(query.page).toEqual({ total: 0, offset: 0, limit: 20 });
  });

  // fof/geoip: adds to the include from an extender.
  test('keep the opt-out when an extension adds to the include', async () => {
    class Extended extends PostListState {}

    extend(Extended.prototype, 'requestParams', function (params: PaginatedListRequestParams) {
      params.include = [...((params.include as string[]) || []), 'ipInfo'];
    });

    const query = await querySentBy(new Extended({}));

    expect(query.include).toBe('user,discussion,ipInfo');
    expect(query.page.total).toBe(0);
    expect(query.withDefaultIncludes).toBe(true);
  });
});
