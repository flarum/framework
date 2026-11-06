import { jest } from '@jest/globals';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import GuardedStorage from '../../../../src/common/resource/guardedStorage';
import ViewPreferences from '../../../../src/common/resource/ViewPreferences';

describe('GuardedStorage', () => {
  beforeEach(() => {
    ViewPreferences.clear();
    ColumnPreferences.clear();
    window.localStorage.clear();
  });

  afterEach(() => jest.restoreAllMocks());

  test('a write is read back from memory, which wins over storage changed since', () => {
    const storage = new GuardedStorage();

    storage.write('k', { a: 1 });
    window.localStorage.setItem('k', JSON.stringify({ a: 2 }));

    expect(storage.read('k')).toEqual({ a: 1 });

    storage.clear();
    expect(storage.read('k')).toEqual({ a: 2 });
  });

  test('a read returns a copy, so callers cannot change the remembered value', () => {
    const storage = new GuardedStorage();

    storage.write('k', { a: 1 });
    (storage.read('k') as any).a = 9;

    expect(storage.read('k')).toEqual({ a: 1 });
  });

  test('unavailable storage: nothing throws, and both preferences keep their writes for the session', () => {
    jest.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new DOMException('QuotaExceededError');
    });
    jest.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new DOMException('SecurityError');
    });

    const storage = new GuardedStorage();
    expect(storage.read('missing')).toBeUndefined();

    ViewPreferences.save('things', { sort: 'name', filter: { status: '' } });
    ColumnPreferences.save('things', new Map([['price', false]]));

    expect(ViewPreferences.load('things')).toEqual({ sort: 'name', filter: { status: '' } });
    expect(ColumnPreferences.load('things')).toEqual(new Map([['price', false]]));
  });
});
