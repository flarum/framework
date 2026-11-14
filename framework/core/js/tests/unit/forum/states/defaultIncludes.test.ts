import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import PostListState from '../../../../src/forum/states/PostListState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => jest.restoreAllMocks());

/** The query a list sends when it loads its first page. */
async function querySentBy(state: PostListState | DiscussionListState): Promise<Record<string, any>> {
  const find = jest.spyOn(app.store, 'find').mockResolvedValue(Object.assign([], { payload: { data: [] } }) as any);

  await (state as any).loadPage(1);

  return find.mock.calls[0][1] as any;
}

// Naming any includes turns off the API's default ones, which extensions add to
// (likes, mentions, flags, ...). Post lists ask for those as well as their own.
describe('post lists', () => {
  test('ask for the default includes as well as their own', async () => {
    const query = await querySentBy(new PostListState({}));

    expect(query.include).toBe('user,discussion');
    expect(query.withDefaultIncludes).toBe(true);
  });
});

describe('discussion lists', () => {
  test('ask only for their own includes', async () => {
    const query = await querySentBy(new DiscussionListState({}));

    expect(query.include).toBeDefined();
    expect(query.withDefaultIncludes).toBeUndefined();
  });
});
