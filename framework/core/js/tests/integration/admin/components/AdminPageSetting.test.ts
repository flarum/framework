import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import AdminPage from '../../../../src/admin/components/AdminPage';
import SettingsModal from '../../../../src/admin/components/SettingsModal';
import { app } from '../../../../src/admin';

beforeAll(() => bootstrapAdmin());

/**
 * `setting(key, fallback)` seeds a stream from the saved value, falling back to
 * the given default when there is nothing saved.
 *
 * Settings are stored in a string column, so a saved `false` comes back as an
 * empty string — the one value a setting can hold that JavaScript reads as
 * absent. Choosing the fallback on falsiness therefore discarded a value the
 * administrator had deliberately saved, and a setting whose default is truthy
 * could never be turned off: it reverted on every reload
 * (flarum/framework#4781). Only the *absence* of a saved value should reach for
 * the default.
 *
 * Two classes expose this, and both are public API: `AdminPage` for settings
 * pages, and `SettingsModal` for settings modals. They are covered together
 * because the bug was fixed in the first while surviving in the second.
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

/**
 * The same contract, on the class settings *modals* are built from. `flarum/audit`
 * and core's own custom header, footer and CSS modals all extend it, and it is
 * part of the public API extensions import as
 * `flarum/admin/components/SettingsModal`.
 */
describe('SettingsModal.setting()', () => {
  class TestModal extends SettingsModal {
    title() {
      return 'Test';
    }

    form() {
      return null;
    }
  }

  const seeded = (saved: string | undefined, fallback: string): unknown => {
    const key = 'test.modal.setting';

    if (saved === undefined) {
      delete app.data.settings[key];
    } else {
      app.data.settings[key] = saved;
    }

    const modal = new TestModal();
    (modal as any).settings = {};

    return modal.setting(key, fallback)();
  };

  test('a saved value is used', () => {
    expect(seeded('1', '')).toBe('1');
  });

  test('the fallback is used when nothing is saved', () => {
    expect(seeded(undefined, 'the-default')).toBe('the-default');
  });

  test('a saved empty string is kept rather than falling back', () => {
    expect(seeded('', '1')).toBe('');
  });
});
