import { jest } from '@jest/globals';
import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import IconImageState, { IModelWithIconImage } from '../../../../src/common/states/IconImageState';

beforeAll(() => bootstrapAdmin());

function model(id: string, url: string | null = null, srcset: string | null = null): IModelWithIconImage {
  return { id: () => id, data: { type: 'groups' }, iconImageUrl: () => url, iconImageSrcset: () => srcset } as any;
}

const requests: any[] = [];
const pushed: any[] = [];

beforeEach(() => {
  requests.length = 0;
  pushed.length = 0;

  // @ts-ignore - the pieces save() touches
  global.app.forum = { attribute: () => 'https://example.com/api' };
  // @ts-ignore
  global.app.request = (options: any) => {
    requests.push(options);
    return Promise.resolve({ data: { type: 'groups', id: '5' } });
  };
  // @ts-ignore
  global.app.store = { pushPayload: (payload: any) => pushed.push(payload) };

  let n = 0;
  URL.createObjectURL = jest.fn(() => `blob:preview-${++n}`);
  URL.revokeObjectURL = jest.fn();
});

describe('IconImageState', () => {
  test('previews the model’s current image until something changes', () => {
    const state = new IconImageState(model('1', 'https://example.com/a.webp', 'https://example.com/a.webp 1x'));

    expect(state.url()).toBe('https://example.com/a.webp');
    expect(state.srcset()).toBe('https://example.com/a.webp 1x');
    expect(state.isDirty()).toBe(false);
  });

  test('a chosen file is previewed from an object URL, without the old srcset', () => {
    const state = new IconImageState(model('1', 'https://example.com/a.webp', 'https://example.com/a.webp 1x'));

    state.set(new File(['x'], 'crest.png', { type: 'image/png' }));

    expect(state.url()).toBe('blob:preview-1');
    expect(state.srcset()).toBeNull();
    expect(state.isDirty()).toBe(true);
  });

  test('choosing another file releases the previous preview', () => {
    const state = new IconImageState(model('1'));

    state.set(new File(['x'], 'a.png', { type: 'image/png' }));
    state.set(new File(['y'], 'b.png', { type: 'image/png' }));

    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:preview-1');
    expect(state.url()).toBe('blob:preview-2');
  });

  test('removing a chosen file from a model without an image leaves nothing to save', async () => {
    const state = new IconImageState(model('1'));

    state.set(new File(['x'], 'a.png', { type: 'image/png' }));
    state.remove();

    expect(state.url()).toBeNull();
    expect(state.isDirty()).toBe(false);

    await state.save();
    expect(requests).toHaveLength(0);
  });

  test('saving uploads the file to the model it is given, such as a newly created record', async () => {
    const state = new IconImageState(model('new'));
    const file = new File(['x'], 'crest.png', { type: 'image/png' });
    const errorHandler = jest.fn();

    state.set(file);
    await state.save(model('5'), { errorHandler });

    expect(requests).toHaveLength(1);
    expect(requests[0].method).toBe('POST');
    expect(requests[0].url).toBe('https://example.com/api/groups/5/icon-image');
    expect(requests[0].errorHandler).toBe(errorHandler);
    expect((requests[0].body as FormData).get('iconImage')).toBe(file);
    expect(pushed).toHaveLength(1);
    expect(state.isDirty()).toBe(false);
  });

  test('removing an existing image saves as a DELETE', async () => {
    const state = new IconImageState(model('5', 'https://example.com/a.webp'));

    state.remove();

    expect(state.url()).toBeNull();
    expect(state.isDirty()).toBe(true);

    await state.save();

    expect(requests).toHaveLength(1);
    expect(requests[0].method).toBe('DELETE');
    expect(requests[0].url).toBe('https://example.com/api/groups/5/icon-image');
  });

  test('a failed upload keeps the change, so the form can save it again', async () => {
    const state = new IconImageState(model('5'));
    // @ts-ignore
    global.app.request = () => Promise.reject(new Error('422'));

    state.set(new File(['x'], 'crest.png', { type: 'image/png' }));

    await expect(state.save()).rejects.toThrow('422');
    expect(state.isDirty()).toBe(true);
    expect(state.url()).toBe('blob:preview-1');
  });
});
