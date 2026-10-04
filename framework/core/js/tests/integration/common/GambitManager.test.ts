import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import GambitManager from '../../../src/common/GambitManager';
import { BooleanGambit, KeyValueGambit } from '../../../src/common/query/IGambit';
import { app } from '../../../src/forum';

const gambits = new GambitManager();

beforeAll(() => bootstrapForum());

describe('GambitManager', () => {
  beforeAll(() => {
    app.boot();
  });

  // -------------------------------------------------------------------------
  // Existing behaviour — must not regress
  // -------------------------------------------------------------------------

  test('gambits are converted to filters', function () {
    expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07 is:hidden author:behz' })).toStrictEqual({
      q: 'lorem',
      created: '2023-07-07',
      hidden: true,
      author: 'behz',
    });
  });

  test('gambits are negated when prefixed with a dash', function () {
    expect(gambits.apply('discussions', { q: 'lorem -created:2023-07-07 -is:hidden -author:behz' })).toStrictEqual({
      q: 'lorem',
      '-created': '2023-07-07',
      '-hidden': true,
      '-author': 'behz',
    });
  });

  test('gambits are only applied for the correct resource type', function () {
    expect(gambits.apply('users', { q: 'lorem created:2023-07-07 is:hidden author:behz email:behz@machine.local' })).toStrictEqual({
      q: 'lorem created:2023-07-07 is:hidden author:behz',
      email: 'behz@machine.local',
    });
    expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07..2023-10-18 is:hidden -author:behz email:behz@machine.local' })).toStrictEqual(
      {
        q: 'lorem email:behz@machine.local',
        created: '2023-07-07..2023-10-18',
        hidden: true,
        '-author': 'behz',
      }
    );
  });

  // -------------------------------------------------------------------------
  // Canonical keyword matching — English keys always work
  // -------------------------------------------------------------------------

  test('canonical English author keyword always matches', function () {
    expect(gambits.apply('discussions', { q: 'author:admin' })).toStrictEqual({
      q: '',
      author: 'admin',
    });
  });

  test('canonical English created keyword always matches', function () {
    expect(gambits.apply('discussions', { q: 'created:2024-01-01' })).toStrictEqual({
      q: '',
      created: '2024-01-01',
    });
  });

  test('canonical English is:hidden always matches', function () {
    expect(gambits.apply('discussions', { q: 'is:hidden' })).toStrictEqual({
      q: '',
      hidden: true,
    });
  });

  test('canonical English is:unread always matches', function () {
    expect(gambits.apply('discussions', { q: 'is:unread' })).toStrictEqual({
      q: '',
      unread: true,
    });
  });

  test('canonical English email keyword always matches for users', function () {
    expect(gambits.apply('users', { q: 'email:admin@machine.local' })).toStrictEqual({
      q: '',
      email: 'admin@machine.local',
    });
  });

  test('canonical English group keyword always matches for users', function () {
    expect(gambits.apply('users', { q: 'group:admins' })).toStrictEqual({
      q: '',
      group: 'admins',
    });
  });

  // -------------------------------------------------------------------------
  // Unrecognised keywords are left in the query string untouched
  // -------------------------------------------------------------------------

  test('unknown keyword is not consumed and remains in q', function () {
    expect(gambits.apply('discussions', { q: 'lorem suivi:admin' })).toStrictEqual({
      q: 'lorem suivi:admin',
    });
  });

  test('unknown boolean keyword is not consumed', function () {
    expect(gambits.apply('discussions', { q: 'is:versteckt' })).toStrictEqual({
      q: 'is:versteckt',
    });
  });

  // -------------------------------------------------------------------------
  // from() — filter object back to query string
  // -------------------------------------------------------------------------

  test('from() converts filter object back to a query string', function () {
    const q = gambits.from('discussions', '', { author: 'admin', hidden: true });
    expect(q).toContain('author:admin');
    expect(q).toContain('is:hidden');
  });

  test('from() ignores unknown filter keys', function () {
    const q = gambits.from('discussions', 'lorem', { unknownKey: 'value' });
    expect(q).toBe('lorem');
  });

  // -------------------------------------------------------------------------
  // match() — callback is called correctly
  // -------------------------------------------------------------------------

  test('match() calls the callback with the correct gambit, matches and negate=false', function () {
    const calls: Array<{ filterKey: string; negate: boolean }> = [];

    gambits.match('discussions', 'author:behz', (gambit, _matches, negate) => {
      calls.push({ filterKey: gambit.filterKey(), negate });
    });

    expect(calls).toStrictEqual([{ filterKey: 'author', negate: false }]);
  });

  test('match() calls the callback with negate=true for a dashed gambit', function () {
    const calls: Array<{ filterKey: string; negate: boolean }> = [];

    gambits.match('discussions', '-is:hidden', (gambit, _matches, negate) => {
      calls.push({ filterKey: gambit.filterKey(), negate });
    });

    expect(calls).toStrictEqual([{ filterKey: 'hidden', negate: true }]);
  });

  test('match() returns remaining query with matched gambits removed', function () {
    const remaining = gambits.match('discussions', 'hello author:behz world', () => {});
    expect(remaining).toBe('hello world');
  });

  test('match() returns full query unchanged when nothing matches', function () {
    const remaining = gambits.match('discussions', 'hello world', () => {});
    expect(remaining).toBe('hello world');
  });

  // -------------------------------------------------------------------------
  // apply() — filter accumulation edge cases
  // -------------------------------------------------------------------------

  test('apply() preserves other filter keys already on the object', function () {
    expect(gambits.apply('discussions', { q: 'author:behz', tag: 'foo' })).toStrictEqual({
      q: '',
      author: 'behz',
      tag: 'foo',
    });
  });

  test('apply() with empty q returns filter unchanged', function () {
    expect(gambits.apply('discussions', { q: '' })).toStrictEqual({ q: '' });
  });

  test('apply() with unknown resource type returns filter unchanged', function () {
    expect(gambits.apply('widgets', { q: 'author:behz' })).toStrictEqual({ q: 'author:behz' });
  });

  // -------------------------------------------------------------------------
  // TDD: Localization alias support
  //
  // When a gambit's key() returns a translated value (e.g. '作者' for 'author'),
  // both the translated keyword AND the English canonical keyword must match.
  // The filter output must be identical in both cases.
  //
  // These tests will FAIL until GambitManager.match() is updated to try both
  // the translated pattern and the canonical English pattern.
  // -------------------------------------------------------------------------

  describe('localization alias support', () => {
    // Gambits whose key() returns a non-English translation but canonicalKey()
    // returns the hardcoded English keyword — the pattern all translated gambits follow.
    class TranslatedBooleanGambit extends BooleanGambit {
      key() { return '隐藏'; }           // Chinese for "hidden"
      canonicalKey() { return 'hidden'; }
      filterKey() { return 'hidden'; }
    }

    class TranslatedKeyValueGambit extends KeyValueGambit {
      key() { return '作者'; }           // Chinese for "author"
      canonicalKey() { return 'author'; }
      hint() { return '用户名'; }
      filterKey() { return 'author'; }
    }

    let localizedGambits: GambitManager;

    beforeAll(() => {
      localizedGambits = new GambitManager();
      localizedGambits.gambits = {
        discussions: [TranslatedBooleanGambit, TranslatedKeyValueGambit],
      };
    });

    test('translated BooleanGambit keyword matches', () => {
      expect(localizedGambits.apply('discussions', { q: 'is:隐藏' })).toStrictEqual({
        q: '',
        hidden: true,
      });
    });

    test('English canonical BooleanGambit keyword still matches when key() is translated', () => {
      // "is:hidden" must still work even though key() returns '隐藏'
      expect(localizedGambits.apply('discussions', { q: 'is:hidden' })).toStrictEqual({
        q: '',
        hidden: true,
      });
    });

    test('translated BooleanGambit and English canonical produce identical filter output', () => {
      const translated = localizedGambits.apply('discussions', { q: 'is:隐藏' });
      const canonical  = localizedGambits.apply('discussions', { q: 'is:hidden' });
      expect(translated).toStrictEqual(canonical);
    });

    test('translated KeyValueGambit keyword matches', () => {
      expect(localizedGambits.apply('discussions', { q: '作者:behz' })).toStrictEqual({
        q: '',
        author: 'behz',
      });
    });

    test('English canonical KeyValueGambit keyword still matches when key() is translated', () => {
      // "author:behz" must still work even though key() returns '作者'
      expect(localizedGambits.apply('discussions', { q: 'author:behz' })).toStrictEqual({
        q: '',
        author: 'behz',
      });
    });

    test('translated KeyValueGambit and English canonical produce identical filter output', () => {
      const translated = localizedGambits.apply('discussions', { q: '作者:behz' });
      const canonical  = localizedGambits.apply('discussions', { q: 'author:behz' });
      expect(translated).toStrictEqual(canonical);
    });

    test('negation works with translated keyword', () => {
      expect(localizedGambits.apply('discussions', { q: '-is:隐藏' })).toStrictEqual({
        q: '',
        '-hidden': true,
      });
    });

    test('negation works with canonical keyword when key() is translated', () => {
      expect(localizedGambits.apply('discussions', { q: '-is:hidden' })).toStrictEqual({
        q: '',
        '-hidden': true,
      });
    });

    test('match() fires callback for translated keyword', () => {
      const calls: string[] = [];
      localizedGambits.match('discussions', 'is:隐藏', (gambit) => {
        calls.push(gambit.filterKey());
      });
      expect(calls).toStrictEqual(['hidden']);
    });

    test('match() fires callback for canonical keyword when key() is translated', () => {
      const calls: string[] = [];
      localizedGambits.match('discussions', 'is:hidden', (gambit) => {
        calls.push(gambit.filterKey());
      });
      expect(calls).toStrictEqual(['hidden']);
    });

    test('completely unrelated keyword is still not consumed', () => {
      expect(localizedGambits.apply('discussions', { q: 'is:versteckt' })).toStrictEqual({
        q: 'is:versteckt',
      });
    });

    test('gambit with key() equal to the canonical English key does not double-match', () => {
      // When key() already returns the English word, there is no alias — it should
      // match once and not be applied twice.
      const result = gambits.apply('discussions', { q: 'is:hidden' });
      expect(result).toStrictEqual({ q: '', hidden: true });
    });
  });
});
