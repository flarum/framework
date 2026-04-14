/**
 * Tests for the individual gambit classes and the BooleanGambit / KeyValueGambit
 * base class behaviour: pattern generation, toFilter, fromFilter, suggestion.
 *
 * These tests pin the current English-keyword behaviour so that any future
 * localization alias work cannot silently regress the canonical paths.
 */
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../../src/forum';

import { BooleanGambit, KeyValueGambit, GambitType } from '../../../../src/common/query/IGambit';
import AuthorGambit from '../../../../src/common/query/discussions/AuthorGambit';
import CreatedGambit from '../../../../src/common/query/discussions/CreatedGambit';
import HiddenGambit from '../../../../src/common/query/discussions/HiddenGambit';
import UnreadGambit from '../../../../src/common/query/discussions/UnreadGambit';
import EmailGambit from '../../../../src/common/query/users/EmailGambit';
import GroupGambit from '../../../../src/common/query/users/GroupGambit';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

// ---------------------------------------------------------------------------
// AuthorGambit (KeyValueGambit)
// ---------------------------------------------------------------------------

describe('AuthorGambit', () => {
  let gambit: AuthorGambit;

  beforeEach(() => {
    gambit = new AuthorGambit();
  });

  test('type is KeyValue', () => {
    expect(gambit.type).toBe(GambitType.KeyValue);
  });

  test('key() returns the English canonical key', () => {
    expect(gambit.key()).toBe('author');
  });

  test('filterKey() returns "author"', () => {
    expect(gambit.filterKey()).toBe('author');
  });

  test('pattern() returns "author:(.+)"', () => {
    expect(gambit.pattern()).toBe('author:(.+)');
  });

  test('toFilter() maps a match to the correct filter object', () => {
    expect(gambit.toFilter(['author:behz', 'behz'], false)).toStrictEqual({ author: 'behz' });
  });

  test('toFilter() negates the filter key when negate=true', () => {
    expect(gambit.toFilter(['author:behz', 'behz'], true)).toStrictEqual({ '-author': 'behz' });
  });

  test('fromFilter() reconstructs the gambit string', () => {
    expect(gambit.fromFilter('behz', false)).toBe('author:behz');
  });

  test('fromFilter() prefixes with dash when negated', () => {
    expect(gambit.fromFilter('behz', true)).toBe('-author:behz');
  });

  test('suggestion() returns key and hint', () => {
    const s = gambit.suggestion();
    expect(s).toHaveProperty('key', 'author');
    expect(s).toHaveProperty('hint');
  });

  test('enabled() is always true', () => {
    expect(gambit.enabled()).toBe(true);
  });

  test('pattern matches a valid author string', () => {
    expect('author:behz').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern does not match an unrelated string', () => {
    expect('auteur:behz').not.toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// CreatedGambit (KeyValueGambit with custom valuePattern)
// ---------------------------------------------------------------------------

describe('CreatedGambit', () => {
  let gambit: CreatedGambit;

  beforeEach(() => {
    gambit = new CreatedGambit();
  });

  test('key() returns "created"', () => {
    expect(gambit.key()).toBe('created');
  });

  test('filterKey() returns "created"', () => {
    expect(gambit.filterKey()).toBe('created');
  });

  test('pattern matches a single date', () => {
    expect('created:2024-01-15').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern matches a date range', () => {
    expect('created:2024-01-01..2024-12-31').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern does not match a plain word', () => {
    expect('created:yesterday').not.toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('toFilter() passes the date string through', () => {
    expect(gambit.toFilter(['created:2024-01-15', '2024-01-15'], false)).toStrictEqual({ created: '2024-01-15' });
  });

  test('toFilter() passes a range string through', () => {
    expect(gambit.toFilter(['created:2024-01-01..2024-12-31', '2024-01-01..2024-12-31'], false)).toStrictEqual({
      created: '2024-01-01..2024-12-31',
    });
  });

  test('fromFilter() reconstructs the gambit string', () => {
    expect(gambit.fromFilter('2024-01-15', false)).toBe('created:2024-01-15');
  });
});

// ---------------------------------------------------------------------------
// HiddenGambit (BooleanGambit)
// ---------------------------------------------------------------------------

describe('HiddenGambit', () => {
  let gambit: HiddenGambit;

  beforeEach(() => {
    gambit = new HiddenGambit();
  });

  test('type is Grouped', () => {
    expect(gambit.type).toBe(GambitType.Grouped);
  });

  test('key() returns "hidden"', () => {
    expect(gambit.key()).toBe('hidden');
  });

  test('filterKey() returns "hidden"', () => {
    expect(gambit.filterKey()).toBe('hidden');
  });

  test('booleanKey() returns "is"', () => {
    expect(gambit.booleanKey()).toBe('is');
  });

  test('groupKey() returns the translated "is" group key', () => {
    expect(gambit.groupKey()).toBe('is');
  });

  test('pattern() returns "is:(hidden)"', () => {
    expect(gambit.pattern()).toBe('is:(hidden)');
  });

  test('toFilter() returns { hidden: true }', () => {
    expect(gambit.toFilter(['is:hidden', 'hidden'], false)).toStrictEqual({ hidden: true });
  });

  test('toFilter() returns { "-hidden": true } when negated', () => {
    expect(gambit.toFilter(['is:hidden', 'hidden'], true)).toStrictEqual({ '-hidden': true });
  });

  test('fromFilter() reconstructs "is:hidden"', () => {
    expect(gambit.fromFilter('hidden', false)).toBe('is:hidden');
  });

  test('fromFilter() reconstructs "-is:hidden" when negated', () => {
    expect(gambit.fromFilter('hidden', true)).toBe('-is:hidden');
  });

  test('suggestion() returns group and key', () => {
    const s = gambit.suggestion();
    expect(s).toHaveProperty('group', 'is');
    expect(s).toHaveProperty('key', 'hidden');
  });

  test('pattern matches "is:hidden"', () => {
    expect('is:hidden').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern does not match a different keyword', () => {
    expect('is:versteckt').not.toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// UnreadGambit (BooleanGambit)
// ---------------------------------------------------------------------------

describe('UnreadGambit', () => {
  let gambit: UnreadGambit;

  beforeEach(() => {
    gambit = new UnreadGambit();
  });

  test('key() returns "unread"', () => {
    expect(gambit.key()).toBe('unread');
  });

  test('filterKey() returns "unread"', () => {
    expect(gambit.filterKey()).toBe('unread');
  });

  test('pattern() returns "is:(unread)"', () => {
    expect(gambit.pattern()).toBe('is:(unread)');
  });

  test('toFilter() returns { unread: true }', () => {
    expect(gambit.toFilter(['is:unread', 'unread'], false)).toStrictEqual({ unread: true });
  });

  test('pattern matches "is:unread"', () => {
    expect('is:unread').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// EmailGambit (KeyValueGambit)
// ---------------------------------------------------------------------------

describe('EmailGambit', () => {
  let gambit: EmailGambit;

  beforeEach(() => {
    gambit = new EmailGambit();
  });

  test('key() returns "email"', () => {
    expect(gambit.key()).toBe('email');
  });

  test('filterKey() returns "email"', () => {
    expect(gambit.filterKey()).toBe('email');
  });

  test('pattern() returns "email:(.+)"', () => {
    expect(gambit.pattern()).toBe('email:(.+)');
  });

  test('toFilter() maps correctly', () => {
    expect(gambit.toFilter(['email:foo@bar.com', 'foo@bar.com'], false)).toStrictEqual({ email: 'foo@bar.com' });
  });

  test('fromFilter() reconstructs correctly', () => {
    expect(gambit.fromFilter('foo@bar.com', false)).toBe('email:foo@bar.com');
  });

  test('pattern matches a valid email string', () => {
    expect('email:foo@bar.com').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// GroupGambit (KeyValueGambit)
// ---------------------------------------------------------------------------

describe('GroupGambit', () => {
  let gambit: GroupGambit;

  beforeEach(() => {
    gambit = new GroupGambit();
  });

  test('key() returns "group"', () => {
    expect(gambit.key()).toBe('group');
  });

  test('filterKey() returns "group"', () => {
    expect(gambit.filterKey()).toBe('group');
  });

  test('pattern() returns "group:(.+)"', () => {
    expect(gambit.pattern()).toBe('group:(.+)');
  });

  test('toFilter() maps correctly', () => {
    expect(gambit.toFilter(['group:admins', 'admins'], false)).toStrictEqual({ group: 'admins' });
  });

  test('fromFilter() reconstructs correctly', () => {
    expect(gambit.fromFilter('admins', false)).toBe('group:admins');
  });
});

// ---------------------------------------------------------------------------
// BooleanGambit base class — array key support
// ---------------------------------------------------------------------------

describe('BooleanGambit with array key()', () => {
  class MultiKeyGambit extends BooleanGambit {
    key() {
      return ['foo', 'bar'];
    }
    filterKey() {
      return 'multi';
    }
  }

  let gambit: MultiKeyGambit;

  beforeEach(() => {
    gambit = new MultiKeyGambit();
  });

  test('pattern() joins multiple keys with | inside the group', () => {
    expect(gambit.pattern()).toBe('is:(foo|bar)');
  });

  test('pattern matches first key', () => {
    expect('is:foo').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern matches second key', () => {
    expect('is:bar').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern does not match an unlisted key', () => {
    expect('is:baz').not.toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// KeyValueGambit base class — custom valuePattern
// ---------------------------------------------------------------------------

describe('KeyValueGambit with custom valuePattern()', () => {
  class DateOnlyGambit extends KeyValueGambit {
    key() { return 'on'; }
    hint() { return 'YYYY-MM-DD'; }
    filterKey() { return 'on'; }
    valuePattern() { return '(\\d{4}-\\d{2}-\\d{2})'; }
  }

  let gambit: DateOnlyGambit;

  beforeEach(() => {
    gambit = new DateOnlyGambit();
  });

  test('pattern() uses the custom valuePattern', () => {
    expect(gambit.pattern()).toBe('on:(\\d{4}-\\d{2}-\\d{2})');
  });

  test('pattern matches a valid date', () => {
    expect('on:2024-01-01').toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });

  test('pattern does not match free text', () => {
    expect('on:yesterday').not.toMatch(new RegExp(`^(-?)${gambit.pattern()}$`, 'i'));
  });
});

// ---------------------------------------------------------------------------
// TDD: canonicalKey() — the English fallback key
//
// Each gambit base class must expose a canonicalKey() method that returns the
// hardcoded English keyword, independent of what key() returns (which may be
// translated). GambitManager will use this to build an alias pattern so that
// the English keyword always works regardless of locale.
//
// These tests will FAIL until canonicalKey() is added to BooleanGambit and
// KeyValueGambit in IGambit.ts.
// ---------------------------------------------------------------------------

describe('TDD: BooleanGambit canonicalKey()', () => {
  class TranslatedBooleanGambit extends BooleanGambit {
    // Simulates a gambit whose key() returns a Chinese translation.
    // canonicalKey() is overridden to return the hardcoded English keyword —
    // this is the pattern every translated gambit subclass must follow.
    key() { return '隐藏'; }
    canonicalKey() { return 'hidden'; }
    filterKey() { return 'hidden'; }
  }

  class UntranslatedBooleanGambit extends BooleanGambit {
    // No canonicalKey() override — defaults to key(), which is already English.
    key() { return 'hidden'; }
    filterKey() { return 'hidden'; }
  }

  test('canonicalKey() exists on BooleanGambit', () => {
    const gambit = new UntranslatedBooleanGambit();
    expect(typeof gambit.canonicalKey).toBe('function');
  });

  test('canonicalKey() returns a string or string[] like key()', () => {
    const gambit = new UntranslatedBooleanGambit();
    const canonical = gambit.canonicalKey();
    expect(typeof canonical === 'string' || Array.isArray(canonical)).toBe(true);
  });

  test('canonicalKey() returns the English keyword when key() is English', () => {
    const gambit = new UntranslatedBooleanGambit();
    expect(gambit.canonicalKey()).toBe('hidden');
  });

  test('canonicalKey() returns the English keyword even when key() is translated', () => {
    const gambit = new TranslatedBooleanGambit();
    // key() returns '隐藏', but canonicalKey() must still return 'hidden'
    expect(gambit.canonicalKey()).toBe('hidden');
  });

  test('canonicalPattern() builds the English fallback pattern', () => {
    const gambit = new TranslatedBooleanGambit();
    expect(typeof (gambit as any).canonicalPattern).toBe('function');
    expect((gambit as any).canonicalPattern()).toBe('is:(hidden)');
  });
});

describe('TDD: KeyValueGambit canonicalKey()', () => {
  class TranslatedKeyValueGambit extends KeyValueGambit {
    // Simulates a gambit whose key() returns a Chinese translation.
    // canonicalKey() is overridden to return the hardcoded English keyword —
    // this is the pattern every translated gambit subclass must follow.
    key() { return '作者'; }
    canonicalKey() { return 'author'; }
    hint() { return '用户名'; }
    filterKey() { return 'author'; }
  }

  class UntranslatedKeyValueGambit extends KeyValueGambit {
    // No canonicalKey() override — defaults to key(), which is already English.
    key() { return 'author'; }
    hint() { return 'username'; }
    filterKey() { return 'author'; }
  }

  test('canonicalKey() exists on KeyValueGambit', () => {
    const gambit = new UntranslatedKeyValueGambit();
    expect(typeof gambit.canonicalKey).toBe('function');
  });

  test('canonicalKey() returns the English keyword when key() is English', () => {
    const gambit = new UntranslatedKeyValueGambit();
    expect(gambit.canonicalKey()).toBe('author');
  });

  test('canonicalKey() returns the English keyword even when key() is translated', () => {
    const gambit = new TranslatedKeyValueGambit();
    expect(gambit.canonicalKey()).toBe('author');
  });

  test('canonicalPattern() builds the English fallback pattern', () => {
    const gambit = new TranslatedKeyValueGambit();
    expect(typeof (gambit as any).canonicalPattern).toBe('function');
    expect((gambit as any).canonicalPattern()).toBe('author:(.+)');
  });
});
