import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import RequestError from '../../../../src/common/utils/RequestError';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { LIVE_VALIDATE_DELAY, resetLiveValidation, saveDocument } from '../../../../src/common/resource/liveValidation';
import ResourceForm from '../../../../src/common/resource/ResourceForm';
import mq from 'mithril-query';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceFormState from '../../../../src/common/resource/ResourceFormState';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const things = descriptor([
  fd('username', 'string', { constraints: { minLength: 3 }, ui: { liveValidate: true } }),
  fd('bio', 'string'),
  fd('owner', 'toOne', { relationship: { types: ['things'] }, ui: { liveValidate: true } }),
]);

function unprocessable(detail: string): RequestError {
  const error = new RequestError(422, '', {} as any, {} as any);
  error.response = { errors: [{ status: '422', detail, source: { pointer: '/data/attributes/username' } }] };

  return error;
}

/** A fake XHR as `config` sees it: a precognitive success unless told otherwise. */
function xhr(status: number = 204, headers: Record<string, string> = {}) {
  return { status, getResponseHeader: (name: string) => headers[name] ?? null, abort: jest.fn() };
}

/**
 * A request mock: the descriptor, then whatever `answer` returns for validate-only requests, its
 * `config` called with `response` (default: a 204).
 */
function serve(answer: (options: any) => Promise<unknown>, response: () => ReturnType<typeof xhr> = () => xhr()) {
  const calls: any[] = [];

  app.request = jest.fn((options: any) => {
    if (options.url.includes('/resource-schemas/')) return Promise.resolve(schemaDocument(things));

    calls.push(options);
    options.xhr = response();
    options.config?.(options.xhr);

    return answer(options).catch((error) => {
      options.errorHandler?.(error);

      throw error;
    });
  }) as any;

  return calls;
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('LiveValidator', () => {
  beforeAll(() => {
    bootAdmin({ debug: true });
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    resetLiveValidation();
  });
  afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
  });

  test('blur sends the save request of the mode with only that field and the Precognition headers', async () => {
    const calls = serve(() => Promise.resolve(null));
    const creating = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    creating.stream('username')('alice');
    creating.stream('bio')('Hello');
    creating.validateField('username');
    await flush();

    expect(calls).toHaveLength(1);
    expect(calls[0].method).toBe('POST');
    expect(calls[0].url).toBe('https://example.test/api/things');
    expect(calls[0].headers).toEqual({ Precognition: 'true', 'Precognition-Validate-Only': 'username' });
    expect(calls[0].body).toEqual({ data: { type: 'things', attributes: { username: 'alice' } } });

    const thing = app.store.pushObject<Thing>({ type: 'things', id: '3', attributes: { username: 'bob' } } as any)!;
    const editing = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    editing.validateField('username');
    await flush();
    expect(calls).toHaveLength(1);

    editing.stream('username')('bobby');
    editing.validateField('username');
    await flush();

    expect(calls[1].method).toBe('PATCH');
    expect(calls[1].url).toBe('https://example.test/api/things/3');
    expect(calls[1].body).toEqual({ data: { type: 'things', id: '3', attributes: { username: 'bobby' } } });
  });

  test('422 sets the field error, 204 clears it, without the global alert', async () => {
    let answer: () => Promise<unknown> = () => Promise.reject(unprocessable('The username is taken.'));
    serve(() => answer());
    const alert = jest.spyOn(app.alerts, 'show');
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    await state.live.validate('username');

    expect(state.errors.username).toBe('The username is taken.');

    answer = () => Promise.resolve(null);
    await state.live.validate('username');

    expect(state.errors.username).toBeUndefined();
    expect(alert).not.toHaveBeenCalled();
  });

  test('other failures are ignored; a client-side error is not overwritten and skips the request', async () => {
    const calls = serve(() => Promise.reject(new RequestError(500, '', {} as any, {} as any)));
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    await state.live.validate('username');
    expect(state.errors).toEqual({});

    state.stream('username')('al');
    state.validateField('username');
    await flush();

    expect(calls).toHaveLength(1);
    expect(state.errors.username).toBeDefined();
  });

  test('an answer to an older check of the field is dropped', async () => {
    const pending: Array<{ resolve: (v: unknown) => void; reject: (e: unknown) => void }> = [];
    serve(() => new Promise((resolve, reject) => pending.push({ resolve, reject })));
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    const first = state.live.validate('username');
    state.stream('username')('alicia');
    const second = state.live.validate('username');

    pending[1].resolve(null);
    await second;
    pending[0].reject(unprocessable('stale'));
    await first;

    expect(state.errors.username).toBeUndefined();
  });

  test('once touched, a change is checked after a pause; an untouched field is not', async () => {
    const calls = serve(() => Promise.resolve(null));
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    jest.useFakeTimers();

    state.stream('username')('alice');
    jest.advanceTimersByTime(LIVE_VALIDATE_DELAY * 2);
    expect(calls).toHaveLength(0);

    state.validateField('username');
    expect(calls).toHaveLength(1);

    state.stream('username')('alicia');
    state.stream('username')('alicia2');
    jest.advanceTimersByTime(LIVE_VALIDATE_DELAY - 1);
    expect(calls).toHaveLength(1);

    jest.advanceTimersByTime(1);
    expect(calls).toHaveLength(2);
    expect(calls[1].body.data.attributes).toEqual({ username: 'alicia2' });
  });

  test('a field without ui.liveValidate is never sent', async () => {
    const calls = serve(() => Promise.resolve(null));
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('bio')('Hello');
    state.validateField('bio');
    await flush();

    expect(calls).toHaveLength(0);
  });

  test('saveDocument serializes relationships like Model.save()', () => {
    const owner = app.store.pushObject<Thing>({ type: 'things', id: '8', attributes: {} } as any)!;

    expect(saveDocument('things', undefined, { relationships: { owner, other: null, many: [owner] } })).toEqual({
      data: {
        type: 'things',
        attributes: {},
        relationships: { owner: { data: { type: 'things', id: '8' } }, many: { data: [{ type: 'things', id: '8' }] } },
      },
    });
  });

  test('a 200 without Precognition-Success turns live validation off for the type, with one debug warning', async () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const calls = serve(
      () => Promise.resolve({ data: { type: 'things', id: '9' } }),
      () => xhr(200)
    );
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    state.setServerError('username', 'old');
    await state.live.validate('username');

    expect(warn).toHaveBeenCalledTimes(1);
    expect(String(warn.mock.calls[0][0])).toContain('Precognition-Success');
    expect(state.errors.username).toBe('old');

    await state.live.validate('username');
    const other = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);
    other.stream('username')('bob');
    await other.live.validate('username');

    expect(calls).toHaveLength(1);
    expect(warn).toHaveBeenCalledTimes(1);
  });

  test('a 200 with Precognition-Success: true counts as valid', async () => {
    serve(
      () => Promise.resolve(null),
      () => xhr(200, { 'Precognition-Success': 'true' })
    );
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    state.setServerError('username', 'old');
    await state.live.validate('username');

    expect(state.errors.username).toBeUndefined();
    expect(state.live.handles('username')).toBe(true);
  });

  test('400 precognition_not_supported turns live validation off silently', async () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const alert = jest.spyOn(app.alerts, 'show');
    const calls = serve(() => {
      const error = new RequestError(400, '', {} as any, {} as any);
      error.response = { errors: [{ status: '400', code: 'precognition_not_supported' }] };

      return Promise.reject(error);
    });
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('username')('alice');
    await state.live.validate('username');

    expect(state.errors).toEqual({});
    expect(state.live.handles('username')).toBe(false);
    expect(warn).not.toHaveBeenCalled();
    expect(alert).not.toHaveBeenCalled();

    await state.live.validate('username');
    expect(calls).toHaveLength(1);
  });

  test('removing a ResourceForm cancels its pending and running checks', async () => {
    const calls = serve(() => new Promise(() => {}));
    const oninit = jest.spyOn(ResourceForm.prototype, 'oninit');
    mq(ResourceForm, { definition: new ResourceDefinition<Thing>('things'), model: null });
    const form = oninit.mock.contexts[0] as ResourceForm<Thing>;
    await flush();
    const state = form.formState!;

    jest.useFakeTimers();

    state.stream('username')('alice');
    state.live.validate('username');
    state.stream('username')('alicia');

    form.onremove({} as any);
    jest.advanceTimersByTime(LIVE_VALIDATE_DELAY * 2);

    expect(calls).toHaveLength(1);
    expect(calls[0].xhr.abort).toHaveBeenCalled();
  });
});
