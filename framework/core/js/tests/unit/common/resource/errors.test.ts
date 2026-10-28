import ItemList from '../../../../src/common/utils/ItemList';
import { mapErrors } from '../../../../src/common/resource/errors';
import type { Field } from '../../../../src/common/resource/types';

function fields(entries: Record<string, Partial<Field<any>>>) {
  const list = new ItemList<Field<any>>();

  Object.entries(entries).forEach(([key, field]) => list.add(key, { label: key, ...field }));

  return list;
}

describe('mapErrors', () => {
  const list = fields({ imageUrl: {}, name: {}, category: {}, sku: { errorKeys: ['article_number'] } });

  test('maps a camelCase attribute pointer to its key', () => {
    expect(mapErrors([{ detail: 'Invalid URL', source: { pointer: '/data/attributes/imageUrl' } }], list)).toEqual({ imageUrl: 'Invalid URL' });
  });

  test('maps a snake_case pointer (Extend\\Validator rules) to the camelCase key', () => {
    expect(mapErrors([{ detail: 'Invalid URL', source: { pointer: '/data/attributes/image_url' } }], list)).toEqual({ imageUrl: 'Invalid URL' });
  });

  test('maps through errorKeys', () => {
    expect(mapErrors([{ detail: 'Taken', source: { pointer: '/data/attributes/article_number' } }], list)).toEqual({ sku: 'Taken' });
  });

  test('maps a translations/{locale}/{field} pointer to the field', () => {
    expect(mapErrors([{ detail: 'Too long', source: { pointer: '/data/attributes/translations/de/name' } }], list)).toEqual({ name: 'Too long' });
  });

  test('maps relationship pointers', () => {
    expect(mapErrors([{ detail: 'Required', source: { pointer: '/data/relationships/category/data' } }], list)).toEqual({ category: 'Required' });
  });

  test('maps the 403 not-writable pointer', () => {
    const errors = mapErrors(
      [{ status: '403', detail: 'Field [createdAt] is not writable', source: { pointer: '/data/attributes/createdAt' } }],
      fields({ createdAt: {} })
    );

    expect(errors).toEqual({ createdAt: 'Field [createdAt] is not writable' });
  });

  test("unmatched and pointer-less errors go to '*'", () => {
    expect(mapErrors([{ detail: 'Unknown thing', source: { pointer: '/data/attributes/unknown' } }, { detail: 'Something broke' }], list)).toEqual({
      '*': 'Unknown thing\nSomething broke',
    });
  });

  test('joins several errors for one field', () => {
    expect(
      mapErrors(
        [
          { detail: 'Too short.', source: { pointer: '/data/attributes/name' } },
          { detail: 'Not unique.', source: { pointer: '/data/attributes/name' } },
        ],
        list
      )
    ).toEqual({ name: 'Too short.\nNot unique.' });
  });
});
