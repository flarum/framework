import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import ThemeSwitcher from '../../../../src/forum/components/ThemeSwitcher';
import ThemeMode, { ColorScheme } from '../../../../src/common/components/ThemeMode';
import { extend } from '../../../../src/common/extend';
import type ItemList from '../../../../src/common/utils/ItemList';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

const options = (rendered: any): Element[] => Array.from(rendered.rootEl.querySelectorAll('.Dropdown-menu button'));

const optionModes = (rendered: any): (string | null)[] =>
  options(rendered).map((el) => el.className.match(/ThemeSwitcher-option--([\w-]+)/)?.[1] ?? null);

describe('ThemeSwitcher', () => {
  beforeAll(() => app.boot());

  const originalSchemes = ThemeMode.colorSchemes;
  const originalIconItems = ThemeSwitcher.prototype.iconItems;
  let user: any;

  afterEach(() => {
    // extend() replaces the method with a wrapper; put the original back so a
    // registration in one test does not leak into the next.
    ThemeSwitcher.prototype.iconItems = originalIconItems;
  });

  beforeEach(() => {
    ThemeMode.colorSchemes = originalSchemes;
    app.setColorScheme = jest.fn();
    app.colorScheme = undefined as any;
    app.session.user = undefined;
    sessionStorage.clear();

    user = {
      preferences: jest.fn(() => ({ colorScheme: 'auto' })),
      savePreferences: jest.fn(() => Promise.resolve()),
    };
  });

  test('offers one option per registered colour scheme', () => {
    const rendered = mq(ThemeSwitcher);

    expect(optionModes(rendered).sort()).toEqual(ThemeMode.colorSchemes.map((s) => s.id).sort());
  });

  test('marks the active scheme', () => {
    app.session.user = user;
    user.preferences = jest.fn(() => ({ colorScheme: ColorScheme.Dark }));

    const rendered = mq(ThemeSwitcher);

    const current = rendered.rootEl.querySelector('.Dropdown-menu [aria-current="true"]');

    expect(current?.querySelector(`.ThemeSwitcher-option--${ColorScheme.Dark}`)).toBeTruthy();
  });

  test('a guest change applies the scheme and remembers it for the tab session', () => {
    app.session.user = undefined;

    const rendered = mq(ThemeSwitcher);

    rendered.click(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark}`);

    expect(app.setColorScheme).toHaveBeenCalledWith(ColorScheme.Dark);
    // Survives a reload, forgotten on tab close — sessionStorage is exactly
    // that lifetime, and it is not sent to the server.
    expect(sessionStorage.getItem('colorScheme')).toBe(ColorScheme.Dark);
  });

  test('a guest change is not saved to the server', () => {
    app.session.user = undefined;

    const rendered = mq(ThemeSwitcher);

    rendered.click(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark}`);

    expect(user.savePreferences).not.toHaveBeenCalled();
  });

  test('a logged-in change saves the preference the same way the settings page does', () => {
    app.session.user = user;

    const rendered = mq(ThemeSwitcher);

    rendered.click(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark}`);

    expect(user.savePreferences).toHaveBeenCalledWith({ colorScheme: ColorScheme.Dark });
  });

  test('a change is reflected as active immediately, without waiting on a reload', () => {
    // The bug this guards: a guest change applied the theme but the control
    // kept showing the old scheme as active, because nothing it read back had
    // changed. The applied scheme is now the source of truth.
    app.session.user = undefined;
    (app.setColorScheme as jest.Mock).mockImplementation((scheme: string) => {
      app.colorScheme = scheme;
    });

    const rendered = mq(ThemeSwitcher);
    rendered.click(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark}`);
    rendered.redraw();

    const current = rendered.rootEl.querySelector('.Dropdown-menu [aria-current="true"]');
    expect(current?.querySelector(`.ThemeSwitcher-option--${ColorScheme.Dark}`)).toBeTruthy();
  });

  test('a scheme registered by an extension appears', () => {
    ThemeMode.colorSchemes = [...originalSchemes, { id: 'sepia', label: 'Sepia' }];

    const rendered = mq(ThemeSwitcher);

    expect(optionModes(rendered)).toContain('sepia');
  });

  test('a non-active option shows its own scheme icon', () => {
    app.session.user = user;
    user.preferences = jest.fn(() => ({ colorScheme: ColorScheme.Dark }));

    const rendered = mq(ThemeSwitcher);

    const light = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Light}`);

    expect(light?.querySelector('.fa-sun')).toBeTruthy();
    expect(light?.querySelector('.fa-check')).toBeFalsy();
  });

  test('the scheme icons use the current Font Awesome prefix', () => {
    const rendered = mq(ThemeSwitcher);

    // Normal contrast is the regular style, high contrast the solid one, so the
    // two are distinguishable at a glance.
    const dark = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark} .Button-icon`);
    const darkHc = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.DarkHighContrast} .Button-icon`);

    expect(dark?.className).toContain('fa-regular');
    expect(darkHc?.className).toContain('fa-solid');
  });

  test('a forced Font Awesome style does not flatten the scheme icons', () => {
    // The regular/solid distinction between normal and high contrast is the
    // point; a forum-wide forced style must not rewrite it away.
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-duotone fa-light' });

    const rendered = mq(ThemeSwitcher);

    const dark = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark} .Button-icon`);
    const darkHc = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.DarkHighContrast} .Button-icon`);

    expect(dark?.className).toContain('fa-regular');
    expect(darkHc?.className).toContain('fa-solid');

    app.forum.pushAttributes({ fontAwesomeForcedStyle: null });
  });

  test('the active option is marked with a check', () => {
    app.session.user = user;
    user.preferences = jest.fn(() => ({ colorScheme: ColorScheme.Dark }));

    const rendered = mq(ThemeSwitcher);

    const active = rendered.rootEl.querySelector(`.Dropdown-menu button.ThemeSwitcher-option--${ColorScheme.Dark}`);

    expect(active?.querySelector('.fa-check')).toBeTruthy();
  });

  test('the toggle shows the icon of the active scheme', () => {
    app.colorScheme = ColorScheme.Dark;

    const rendered = mq(ThemeSwitcher);

    const toggleIcon = rendered.rootEl.querySelector('.Dropdown-toggle .Button-icon');

    expect(toggleIcon?.className).toContain('fa-moon');
  });

  test('for the system preference, the toggle shows the icon of the resolved theme', () => {
    // "Auto" has no icon of its own — it shows whatever the OS resolved to, so
    // the control reflects what the reader is actually seeing.
    app.colorScheme = ColorScheme.Auto;
    app.getSystemColorSchemePreference = jest.fn(() => ColorScheme.Dark);

    const rendered = mq(ThemeSwitcher);

    const toggleIcon = rendered.rootEl.querySelector('.Dropdown-toggle .Button-icon');

    expect(toggleIcon?.className).toContain('fa-moon');
  });

  test('an extension can register an icon for its own scheme', () => {
    ThemeMode.colorSchemes = [...originalSchemes, { id: 'sepia', label: 'Sepia' }];
    extend(ThemeSwitcher.prototype, 'iconItems', (items: ItemList<string>) => {
      items.add('sepia', 'fa-solid fa-mug-hot');
    });

    const rendered = mq(ThemeSwitcher);

    const sepia = rendered.rootEl.querySelector('.Dropdown-menu button.ThemeSwitcher-option--sepia .Button-icon');

    expect(sepia?.className).toContain('fa-mug-hot');
  });

  test('a scheme with no registered icon falls back to the default', () => {
    ThemeMode.colorSchemes = [...originalSchemes, { id: 'plain', label: 'Plain' }];

    const rendered = mq(ThemeSwitcher);

    const plain = rendered.rootEl.querySelector('.Dropdown-menu button.ThemeSwitcher-option--plain .Button-icon');

    expect(plain?.className).toContain('fa-circle-half-stroke');
  });

  test('an extension can override the icon of a core scheme', () => {
    app.colorScheme = ColorScheme.Dark;
    extend(ThemeSwitcher.prototype, 'iconItems', (items: ItemList<string>) => {
      items.setContent(ColorScheme.Dark, 'fa-solid fa-star');
    });

    const rendered = mq(ThemeSwitcher);

    expect(rendered.rootEl.querySelector('.Dropdown-toggle .fa-star')).toBeTruthy();
  });

  test('the toggle carries a text label, so the mobile drawer can show it', () => {
    app.session.user = user;
    user.preferences = jest.fn(() => ({ colorScheme: ColorScheme.Dark }));

    const rendered = mq(ThemeSwitcher);

    const label = rendered.rootEl.querySelector('.Dropdown-toggle .Button-labelText');

    expect(label?.textContent?.trim()).toBeTruthy();
  });
});
