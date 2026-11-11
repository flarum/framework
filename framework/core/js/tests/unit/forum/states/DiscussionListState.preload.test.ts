import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import { makeDiscussion } from '../../../factory';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

let requests: any[];
const originalRequest = app.request;

beforeEach(() => {
  app.store.data = {};
  requests = [];

  app.request = ((options: any) => {
    requests.push(options.params);

    return Promise.resolve({ data: [makeDiscussion({ id: '2' })], links: {}, meta: {} });
  }) as any;
});

afterAll(() => {
  app.request = originalRequest;
});

/**
 * Stand in for a page the server rendered with a list of discussions
 * preloaded: what `Forum\Content\Index` puts in the payload for `?q=`, a plain
 * text search, since the server doesn't know the frontend's gambits.
 */
function preload() {
  app.data.apiDocument = { data: [makeDiscussion({ id: '1' })], links: {}, meta: { perPage: 20 } } as any;
  (app as any).initialRoute = window.location.href;
}

function listedIds(state: DiscussionListState): string[] {
  return state.getPages().flatMap((page) => page.items.map((discussion) => discussion.id()!));
}

describe('DiscussionListState first load from a preloaded document (#5460)', () => {
  test('uses the preloaded document for a list without a search', async () => {
    preload();

    const state = new DiscussionListState({});
    await state.refresh();

    expect(requests).toEqual([]);
    expect(listedIds(state)).toEqual(['1']);
  });

  test('uses the preloaded document for a plain text search', async () => {
    preload();

    const state = new DiscussionListState({ q: 'hello world' });
    await state.refresh();

    expect(requests).toEqual([]);
    expect(listedIds(state)).toEqual(['1']);
  });

  test('requests the gambit-parsed filters for a search holding a gambit', async () => {
    preload();

    const state = new DiscussionListState({ q: 'author:mod hello' });
    await state.refresh();

    expect(requests).toHaveLength(1);
    expect(requests[0].filter).toMatchObject({ author: 'mod', q: 'hello' });
    expect(listedIds(state)).toEqual(['2']);
  });

  test('takes the preloaded document even when it requests the list again, so no later load reuses it', async () => {
    preload();

    await new DiscussionListState({ q: 'author:mod' }).refresh();

    expect(app.data.apiDocument).toBeNull();
  });

  test('requests the right page of a search holding a gambit loaded beyond page 1', async () => {
    preload();

    const state = new DiscussionListState({ q: 'author:mod' } as any);
    await state.refresh(3);

    expect(requests).toHaveLength(1);
    expect(requests[0].page).toMatchObject({ offset: 40, limit: 20 });
  });
});
