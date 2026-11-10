import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import PaginatedListState, { PaginatedListParams } from '../../../../src/common/states/PaginatedListState';
import Model from '../../../../src/common/Model';
import RequestError from '../../../../src/common/utils/RequestError';

beforeAll(() => bootstrapForum());

/** A list whose every page fails to load, as one does offline. */
class FailingState extends PaginatedListState<Model, PaginatedListParams> {
  constructor() {
    super({}, 1, 20);
  }

  get type() {
    return 'test';
  }

  protected loadPage(): Promise<any> {
    return Promise.reject(new RequestError(0, '', { url: '/api/test' } as any, {} as any));
  }
}

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

// The request has already shown its error (see Application.request()), so a
// caller that starts a load without waiting for it, as a "Load more" button or
// a page's first load does, mustn't report the failure a second time.
describe('PaginatedListState when a load fails', () => {
  test('a refresh nobody waits for leaves the error to the request', async () => {
    new FailingState().refresh();

    await settle();
  });

  test('a caller that waits for a refresh still sees it fail', async () => {
    const state = new FailingState();

    await expect(state.refresh()).rejects.toBeInstanceOf(RequestError);
    expect(state.isInitialLoading()).toBe(false);
  });

  test('loading the next page without waiting leaves the error to the request', async () => {
    new FailingState().loadNext();

    await settle();
  });

  test('loading the previous page without waiting leaves the error to the request', async () => {
    const state = new FailingState();
    (state as any).pages = [{ number: 2, items: [], hasPrev: true }];

    state.loadPrev();

    await settle();
  });
});
