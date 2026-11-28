import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import AppearancePage from '../../../../src/admin/components/AppearancePage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

const themeSelectorToggle = (page: any): Element | null =>
  page.rootEl.querySelector('[data-setting="show_theme_selector"], .Form-group input[name="show_theme_selector"]') ??
  Array.from(page.rootEl.querySelectorAll('.Form-group')).find((g: any) => /Show theme selector/i.test(g.textContent)) ??
  null;

describe('AppearancePage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(AppearancePage);

    expect(page).toHaveElement('.AppearancePage');
  });

  test('offers the show-theme-selector toggle when the forum leaves the scheme to the reader', () => {
    app.data.settings.color_scheme = 'auto';

    expect(themeSelectorToggle(mq(AppearancePage))).toBeTruthy();
  });

  test('hides the show-theme-selector toggle when the admin has forced a scheme', () => {
    // There is nothing for a reader to switch, so surfacing the header control
    // is not an option to offer — the same way the language selector toggle is
    // absent when there is only one language.
    app.data.settings.color_scheme = 'dark';

    expect(themeSelectorToggle(mq(AppearancePage))).toBeFalsy();

    app.data.settings.color_scheme = 'auto';
  });

  test.each([
    ['no theme sets the app icon', {}],
    ['a theme sets the app icon', { app_icon: 'Theme' }],
  ])('shows one save button in the Logos & icons section when %s', (_, themeImages) => {
    const previous = app.data.themeImages;
    app.data.themeImages = themeImages;

    const page = mq(AppearancePage);

    expect(page.rootEl.querySelectorAll('#appearance-logos button[type="submit"]')).toHaveLength(1);

    app.data.themeImages = previous;
  });
});
