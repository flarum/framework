import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import HeaderSecondary from '../../../../src/forum/components/HeaderSecondary';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('HeaderSecondary', () => {
  beforeAll(() => app.boot());

  beforeEach(() => {
    // The forum leaves the choice to the reader, and surfaces the control, by
    // default.
    app.allowUserColorScheme = true;
    app.forum.pushAttributes({ showThemeSelector: true });
  });

  test('renders', () => {
    const header = mq(HeaderSecondary);

    expect(header).toBeTruthy();
  });

  test('includes the theme switcher', () => {
    const header = mq(HeaderSecondary);

    expect(header).toHaveElement('.ThemeSwitcher');
  });

  test('places the theme switcher just above the notifications control', () => {
    // Notifications sit at 10 and only exist for a logged-in user. The switcher
    // sits at 12 (higher priority renders further from the session controls),
    // leaving 11 free for an extension that wants to slot between the two.
    const items = new HeaderSecondary().items();

    expect(items.has('themeSwitcher')).toBe(true);
    expect(items.getPriority('themeSwitcher')).toBe(12);
  });

  test('hides the theme switcher when the forum has forced a theme', () => {
    // When an admin fixes the forum's colour scheme there is nothing for the
    // reader to choose, so the control does not appear — the same condition the
    // user settings page uses to hide its theme section.
    app.allowUserColorScheme = false;

    const items = new HeaderSecondary().items();

    expect(items.has('themeSwitcher')).toBe(false);
    expect(mq(HeaderSecondary)).not.toHaveElement('.ThemeSwitcher');
  });

  test('hides the theme switcher when the admin has turned the selector off', () => {
    // Users may still change theme from their settings; the admin has just
    // chosen not to surface the header control.
    app.forum.pushAttributes({ showThemeSelector: false });

    const items = new HeaderSecondary().items();

    expect(items.has('themeSwitcher')).toBe(false);
    expect(mq(HeaderSecondary)).not.toHaveElement('.ThemeSwitcher');
  });
});
