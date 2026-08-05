import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import AdminPage from '../../../../src/admin/components/AdminPage';
import { app } from '../../../../src/admin';

beforeAll(() => bootstrapAdmin());

/**
 * `setting(key, fallback)` seeds a stream from the saved value, falling back to
 * the given default when there is nothing saved.
 *
 * Settings are stored in a string column, so a saved `false` comes back as `''`
 * and a saved `0` as `'0'` — both falsy in JavaScript. Choosing the fallback on
 * falsiness therefore discarded values an administrator had deliberately saved,
 * and a setting whose default is truthy could never be turned off: it reverted
 * on every reload (flarum/framework#4781). Only the *absence* of a saved value
 * should reach for the default.
 */
describe('AdminPage.setting()', () => {
  class TestPage extends AdminPage {
    content() {
      return null;
    }
  }

  /** The value `setting()` seeds its stream with, for a given saved value. */
  const seeded = (saved: string | undefined, fallback: string): string => {
    const key = 'test.setting';

    if (saved === undefined) {
      delete app.data.settings[key];
    } else {
      app.data.settings[key] = saved;
    }

    // A fresh page each time: `setting()` caches its streams per instance.
    const page = new TestPage();
    (page as any).settings = {};

    return page.setting(key, fallback)();
  };

  test('a saved value is used', () => {
    expect(seeded('1', '')).toBe('1');
  });

  test('the fallback is used when nothing is saved', () => {
    expect(seeded(undefined, 'the-default')).toBe('the-default');
  });

  /**
   * The reported case: a boolean setting defaulting to on, switched off. `false`
   * is stored as an empty string, which must not be mistaken for "unset".
   */
  test('a saved empty string is kept rather than falling back', () => {
    expect(seeded('', '1')).toBe('');
  });

  /**
   * `'0'` was never affected — the string is truthy in JavaScript, unlike the
   * number. Pinned so the distinction is not lost if this is ever revisited:
   * the empty string is the only value a setting can hold that JavaScript reads
   * as absent.
   */
  test('a saved "0" is kept', () => {
    expect(seeded('0', '10')).toBe('0');
  });
});
