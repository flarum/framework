import { jest } from '@jest/globals';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';

const KEY = 'flarum.resourceTable.things.columns';

describe('ColumnPreferences', () => {
  beforeEach(() => {
    ColumnPreferences.clear();
    window.localStorage.clear();
  });

  afterEach(() => jest.restoreAllMocks());

  test('round-trips the explicit column choices of a type under flarum.resourceTable.{type}.columns', () => {
    expect(ColumnPreferences.load('things')).toEqual(new Map());

    ColumnPreferences.save(
      'things',
      new Map([
        ['price', false],
        ['notes', true],
      ])
    );

    expect(JSON.parse(window.localStorage.getItem(KEY)!)).toEqual({ price: false, notes: true });

    ColumnPreferences.clear();
    expect(ColumnPreferences.load('things')).toEqual(
      new Map([
        ['price', false],
        ['notes', true],
      ])
    );
    expect(ColumnPreferences.load('others')).toEqual(new Map());
  });

  test('a stored array reads as no choices', () => {
    window.localStorage.setItem(KEY, JSON.stringify(['price', 'notes']));

    expect(ColumnPreferences.load('things')).toEqual(new Map());
  });

  test('a throwing setItem falls back to memory', () => {
    jest.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new DOMException('QuotaExceededError');
    });

    expect(() => ColumnPreferences.save('things', new Map([['price', false]]))).not.toThrow();
    expect(ColumnPreferences.load('things')).toEqual(new Map([['price', false]]));
  });

  test('a throwing getItem, or garbage in storage, reads as no choices', () => {
    window.localStorage.setItem(KEY, '{not json');
    expect(ColumnPreferences.load('things')).toEqual(new Map());

    window.localStorage.setItem(KEY, '"price"');
    expect(ColumnPreferences.load('things')).toEqual(new Map());

    // Non-boolean values are dropped, the rest kept.
    window.localStorage.setItem(KEY, '{"a":1,"b":true}');
    expect(ColumnPreferences.load('things')).toEqual(new Map([['b', true]]));

    jest.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new DOMException('SecurityError');
    });
    expect(ColumnPreferences.load('things')).toEqual(new Map());
  });
});
