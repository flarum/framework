import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import DialogMessage from '../../../../src/common/models/DialogMessage';
import MessageStreamState from '../../../../src/forum/states/MessageStreamState';

/**
 * The stream holds pages of offsets from the newest end of the conversation.
 * A message arriving live has to keep those offsets true, and join the
 * conversation only where the newest end is held; the base push() made a page
 * of its own, and the next load of older messages skipped a page of history.
 */

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any)['dialog-messages'] = DialogMessage;
});

afterEach(() => jest.restoreAllMocks());

const message = (number: number) =>
  app.store.pushObject({ type: 'dialog-messages', id: String(number), attributes: { number } } as any) as DialogMessage;

/** A page as the API returns it: newest first, this far from the newest end. */
function page(numbers: number[], offset: number) {
  return Object.assign(numbers.map(message), {
    payload: { data: [], links: offset > 0 ? { prev: 'newer' } : {}, meta: { page: { offset } } },
  });
}

function holding(...pages: Array<ReturnType<typeof page>>): MessageStreamState {
  const state = new MessageStreamState({ filter: { dialog: '1' }, sort: '-number' });

  (state as any).pages = pages.map((items, i) => ({ number: i + 1, items, hasPrev: !!items.payload.links.prev, hasNext: false }));

  return state;
}

const numbers = (state: MessageStreamState) => state.getAllItems().map((m) => m.number());
const offsets = (state: MessageStreamState) => state.getPages().map((p: any) => p.items.payload.meta.page.offset);

describe('push', () => {
  it('joins the newest page when the newest end is held, and moves every offset along', () => {
    const state = holding(page([3, 2, 1], 0));

    state.push(message(4));

    expect(numbers(state)).toEqual([4, 3, 2, 1]);
    expect(offsets(state)).toEqual([1]);
  });

  // It will arrive with the newer messages, in order, when they are loaded.
  it('stays out of a block held away from the newest end, but still moves its offsets', () => {
    const state = holding(page([30, 29, 28], 50));

    state.push(message(99));

    expect(numbers(state)).toEqual([30, 29, 28]);
    expect(offsets(state)).toEqual([51]);
  });

  it('does not hold a message twice', () => {
    const state = holding(page([3, 2, 1], 0));

    state.push(message(3));

    expect(numbers(state)).toEqual([3, 2, 1]);
  });

  it('copes with nothing loaded', () => {
    const state = holding();

    state.push(message(1));

    expect(state.hasItems()).toBe(false);
  });
});

describe('catchUp', () => {
  const newestPage = (numbers: number[]) => jest.spyOn(app.store, 'find').mockResolvedValue(page(numbers, 0) as never);

  it('takes in what the newest page holds that the stream does not, oldest first', async () => {
    const state = holding(page([3, 2, 1], 0));
    newestPage([5, 4, 3]);

    await state.catchUp();

    expect(numbers(state)).toEqual([5, 4, 3, 2, 1]);
    expect(offsets(state)).toEqual([2]);
  });

  it('starts over from the newest end when more than a page arrived', async () => {
    const state = holding(page([3, 2, 1], 0));
    newestPage([9, 8, 7]);

    await state.catchUp();

    expect(numbers(state)).toEqual([9, 8, 7]);
    expect(state.getPages()).toHaveLength(1);
  });

  it('asks for nothing when the newest end is not held', async () => {
    const state = holding(page([30, 29, 28], 50));
    const find = newestPage([99]);

    await state.catchUp();

    expect(find).not.toHaveBeenCalled();
    expect(numbers(state)).toEqual([30, 29, 28]);
  });

  it('leaves the conversation as it was when the request fails', async () => {
    const state = holding(page([3, 2, 1], 0));
    jest.spyOn(app.store, 'find').mockRejectedValue(new Error('offline') as never);

    await state.catchUp();

    expect(numbers(state)).toEqual([3, 2, 1]);
  });
});
