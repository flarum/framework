import ItemList from '../../../../src/common/utils/ItemList';
import { errorKey, mapErrors, pointerPath } from '../../../../src/common/resource/errors';
import type { Field, JsonApiError } from '../../../../src/common/resource/types';

function fields(entries: Record<string, Partial<Field<any>>>) {
  const list = new ItemList<Field<any>>();

  Object.entries(entries).forEach(([key, field]) => list.add(key, { label: key, ...field }));

  return list;
}

const at = (pointer: string, detail = 'Bad'): JsonApiError => ({ detail, source: { pointer } });

/** The resolved errors as `{ key: [[message, path]] }`, for terse expectations. */
function mapped(errors: JsonApiError[], list: ItemList<Field<any>>) {
  return Object.fromEntries(Object.entries(mapErrors(errors, list)).map(([key, resolved]) => [key, resolved.map((e) => [e.message, e.path])]));
}

describe('mapErrors', () => {
  const list = fields({ imageUrl: {}, name: {}, category: {}, sku: { errorKeys: ['article_number'] } });

  test('maps a pointer naming the field to its key, without a path', () => {
    expect(mapped([at('/data/attributes/imageUrl', 'Invalid URL')], list)).toEqual({ imageUrl: [['Invalid URL', undefined]] });
  });

  test('keeps the raw error and its pointer on the resolved error', () => {
    const error = at('/data/attributes/name');

    expect(mapErrors([error], list)).toEqual({ name: [{ message: 'Bad', pointer: '/data/attributes/name', error }] });
  });

  test('maps a snake_case pointer (Extend\\Validator rules) to the camelCase key', () => {
    expect(mapped([at('/data/attributes/image_url')], list)).toEqual({ imageUrl: [['Bad', undefined]] });
  });

  test('maps a pointer below the field with the rest as path', () => {
    const list = fields({ translationMap: {}, name: {} });

    expect(mapped([at('/data/attributes/translationMap/de/name')], list)).toEqual({ translationMap: [['Bad', 'de/name']] });
  });

  test('maps through errorKeys', () => {
    expect(mapped([at('/data/attributes/article_number', 'Taken')], list)).toEqual({ sku: [['Taken', undefined]] });
  });

  test('errorKeys claim a path and everything below it, on whole segments', () => {
    const list = fields({ switcher: { errorKeys: ['translationMap/de'] } });

    expect(
      mapped(
        [at('/data/attributes/translationMap/de'), at('/data/attributes/translationMap/de/name'), at('/data/attributes/translationMap/den')],
        list
      )
    ).toEqual({
      switcher: [
        ['Bad', undefined],
        ['Bad', 'name'],
      ],
      '*': [['Bad', undefined]],
    });
  });

  test('the most specific match wins across fields', () => {
    const list = fields({
      switcher: { errorKeys: ['translationMap/de'] },
      name: { errorKeys: ['translationMap/de/name'] },
      translationMap: {},
    });

    expect(
      mapped(
        [at('/data/attributes/translationMap/de/name'), at('/data/attributes/translationMap/de'), at('/data/attributes/translationMap/fr')],
        list
      )
    ).toEqual({ name: [['Bad', undefined]], switcher: [['Bad', undefined]], translationMap: [['Bad', 'fr']] });
  });

  test('never guesses a field from a single segment of a longer pointer', () => {
    const list = fields({ url: {}, name: {}, id: { errorKeys: [] } });

    expect(
      mapped([at('/data/attributes/meta/url'), at('/data/attributes/translations/de/name'), at('/data/attributes/translationMap/id')], list)
    ).toEqual({
      '*': [
        ['Bad', undefined],
        ['Bad', undefined],
        ['Bad', undefined],
      ],
    });
  });

  test("'*' in errorKeys is a literal segment: it claims the base row, not the unmatched errors", () => {
    const list = fields({ switcher: { errorKeys: ['translationMap/*'] }, star: { errorKeys: ['*'] } });

    expect(mapped([at('/data/attributes/translationMap/*/name'), at('/data/attributes/unknown')], list)).toEqual({
      switcher: [['Bad', 'name']],
      '*': [['Bad', undefined]],
    });
  });

  test('decodes ~1 and ~0 in pointer tokens; errorKeys are written escaped', () => {
    const list = fields({ links: { errorKeys: ['links/a~1b'] }, tilde: {} });

    expect(mapped([at('/data/attributes/links/a~1b/x~01'), at('/data/attributes/tilde/~0')], list)).toEqual({
      links: [['Bad', 'x~1']],
      tilde: [['Bad', '~']],
    });
  });

  test('maps relationship pointers', () => {
    expect(mapped([at('/data/relationships/category/data'), at('/data/relationships/category')], list)).toEqual({
      category: [
        ['Bad', undefined],
        ['Bad', undefined],
      ],
    });
  });

  test('maps the 403 not-writable pointer', () => {
    const errors = [{ status: '403', detail: 'Field [createdAt] is not writable', source: { pointer: '/data/attributes/createdAt' } }];

    expect(mapped(errors, fields({ createdAt: {} }))).toEqual({ createdAt: [['Field [createdAt] is not writable', undefined]] });
  });

  test("unmatched and pointer-less errors go to '*'", () => {
    expect(mapped([at('/data/attributes/unknown', 'Unknown thing'), { detail: 'Something broke' }], list)).toEqual({
      '*': [
        ['Unknown thing', undefined],
        ['Something broke', undefined],
      ],
    });
  });

  test('keeps several errors for one field', () => {
    expect(mapped([at('/data/attributes/name', 'Too short.'), at('/data/attributes/name', 'Not unique.')], list)).toEqual({
      name: [
        ['Too short.', undefined],
        ['Not unique.', undefined],
      ],
    });
  });
});

describe('pointerPath', () => {
  test('the decoded segments below /data/attributes or /data/relationships', () => {
    expect(pointerPath('/data/attributes/translationMap/a~1b/name')).toEqual(['translationMap', 'a/b', 'name']);
    expect(pointerPath('/data/relationships/category/data/0')).toEqual(['category']);
    expect(pointerPath('/meta/x')).toBeNull();
    expect(pointerPath(undefined)).toBeNull();
  });
});

describe('errorKey', () => {
  test('escapes each segment as a JSON pointer token and joins them', () => {
    expect(errorKey('translationMap', 'a/b', 'x~y')).toBe('translationMap/a~1b/x~0y');
  });

  test('round-trips through pointerPath', () => {
    expect(pointerPath(`/data/attributes/${errorKey('translationMap', 'a/b', '~1')}`)).toEqual(['translationMap', 'a/b', '~1']);
  });
});
