import { bootAdmin } from './setup';
import { nameIsValid, parseConnections, parseTokens, serializeConnections, withoutKeys } from '../../../src/admin/utils/settings';

beforeAll(() => bootAdmin());

describe('parseTokens', () => {
  it('reads a whole number above zero', () => {
    expect(parseTokens('1000')).toBe(1000);
    expect(parseTokens(' 42 ')).toBe(42);
    expect(parseTokens(7)).toBe(7);
  });

  it('reads nothing as no limit', () => {
    expect(parseTokens('')).toBeNull();
    expect(parseTokens('  ')).toBeNull();
    expect(parseTokens(undefined)).toBeNull();
  });

  it("refuses what isn't a limit, rather than reading part of it", () => {
    for (const value of ['1e3', '12abc', '0', '-5', '1.5', 'lots']) {
      expect(parseTokens(value)).toBe(false);
    }
  });
});

describe('connection names', () => {
  it('start with a letter, so none is ever a number', () => {
    expect(nameIsValid('claude')).toBe(true);
    expect(nameIsValid('Claude_2-b')).toBe(true);
    expect(nameIsValid('123')).toBe(false);
    expect(nameIsValid('1claude')).toBe(false);
    expect(nameIsValid('my claude')).toBe(false);
    expect(nameIsValid('a'.repeat(51))).toBe(false);
  });
});

describe('keys', () => {
  it('are known only as saved or not, from the payload', () => {
    expect(parseConnections('{"claude":{"driver":"anthropic"},"local":{"driver":"ollama"}}', ['claude'])).toEqual({
      claude: { driver: 'anthropic', hasKey: true },
      local: { driver: 'ollama', hasKey: false },
    });
  });

  it('are sent only when typed or removed, never the flag', () => {
    const json = serializeConnections({
      kept: { driver: 'anthropic', hasKey: true },
      typed: { driver: 'anthropic', key: 'sk-new', hasKey: false },
      removed: { driver: 'ollama', key: '' },
    });

    expect(JSON.parse(json)).toEqual({
      kept: { driver: 'anthropic' },
      typed: { driver: 'anthropic', key: 'sk-new' },
      removed: { driver: 'ollama', key: '' },
    });
  });

  it('are dropped from the page once saved, leaving only whether one is set', () => {
    expect(
      withoutKeys({
        typed: { driver: 'anthropic', key: 'sk-new' },
        removed: { driver: 'ollama', key: '' },
        kept: { driver: 'anthropic', hasKey: true },
      })
    ).toEqual({
      typed: { driver: 'anthropic', hasKey: true },
      removed: { driver: 'ollama', hasKey: false },
      kept: { driver: 'anthropic', hasKey: true },
    });
  });
});
