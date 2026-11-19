import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import PostListState from '../../../../src/forum/states/PostListState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

let requests: any[];
const originalRequest = app.request;

const post = (id: string) => ({ type: 'posts', id, attributes: { contentType: 'comment' } });

beforeEach(() => {
  app.store.data = {};
  requests = [];

  app.request = ((options: any) => {
    requests.push(options.params);

    return Promise.resolve({ data: [post('2')], links: {}, meta: {} });
  }) as any;
});

afterAll(() => {
  app.request = originalRequest;
});

/**
 * Stand in for a page the server rendered with a list of posts preloaded:
 * what `Forum\Content\Posts` puts in the payload for `?q=`, a plain text
 * search, since the server doesn't know the frontend's gambits.
 */
function preload() {
  app.data.apiDocument = { data: [post('1')], links: {}, meta: { perPage: 20 } } as any;
  (app as any).initialRoute = window.location.href;
}

function listedIds(state: PostListState): string[] {
  return state.getPages().flatMap((page) => page.items.map((p) => p.id()!));
}

describe('PostListState first load from a preloaded document (#5460)', () => {
  test('uses the preloaded document for a plain text search', async () => {
    preload();

    const state = new PostListState({ q: 'hello' } as any);
    await state.refresh();

    expect(requests).toEqual([]);
    expect(listedIds(state)).toEqual(['1']);
  });

  test('requests the gambit-parsed filters for a search holding a gambit', async () => {
    preload();

    const state = new PostListState({ q: 'author:mod hello' } as any);
    await state.refresh();

    expect(requests).toHaveLength(1);
    expect(requests[0].filter).toMatchObject({ author: 'mod', q: 'hello' });
    expect(listedIds(state)).toEqual(['2']);
  });

  test('requests the right page of a search holding a gambit loaded beyond page 1', async () => {
    preload();

    const state = new PostListState({ q: 'author:mod' } as any);
    await state.refresh(3);

    expect(requests).toHaveLength(1);
    expect(requests[0].page).toMatchObject({ offset: 40, limit: 20 });
  });
});
