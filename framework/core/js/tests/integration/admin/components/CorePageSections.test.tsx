import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import AppearancePage from '../../../../src/admin/components/AppearancePage';
import AdvancedPage from '../../../../src/admin/components/AdvancedPage';
import BasicsPage from '../../../../src/admin/components/BasicsPage';
import FormSection from '../../../../src/admin/components/FormSection';
import { extend } from '../../../../src/common/extend';
import type ItemList from '../../../../src/common/utils/ItemList';
import type Mithril from 'mithril';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

/**
 * Core settings pages are split into SettingsSections, while the ItemLists and
 * registry they are built from stay the extension points they were. Additions
 * that don't name a section are shown next to the item before them.
 */
describe('core admin pages in sections', () => {
  beforeAll(() => {
    app.boot();

    extend(AppearancePage.prototype, 'brandingItems', function (items: ItemList<Mithril.Children>) {
      items.add('acme-near-logos', <div className="acme-near-logos" />, 84.5);
      items.add('acme-near-css', <div className="acme-near-css" />, 55);
    });

    extend(AdvancedPage.prototype, 'sectionItems', function (items: ItemList<Mithril.Children>) {
      items.add('acme', <FormSection label="Acme">acme body</FormSection>, 50);
    });

    app.registry
      .for('core-basics')
      .registerSetting({ type: 'text', setting: 'acme_unsectioned', label: 'Acme unsectioned' })
      .registerSetting({ type: 'text', setting: 'acme_tagline', label: 'Acme tagline', section: 'forum' });
  });

  test('Appearance shows colors, logos and custom code sections', () => {
    const page = mq(AppearancePage);

    expect(page).toHaveElement('#appearance-colors.SettingsSection');
    expect(page).toHaveElement('#appearance-logos.SettingsSection');
    expect(page).toHaveElement('#appearance-custom-code.SettingsSection');
  });

  test('items other extensions add to brandingItems land in the section of their neighbour', () => {
    const page = mq(AppearancePage);

    expect(page).toHaveElement('#appearance-logos .acme-near-logos');
    expect(page).toHaveElement('#appearance-custom-code .acme-near-css');
  });

  test('Basics settings are in sections, including a setting another extension placed in one', () => {
    const page = mq(BasicsPage);

    expect(page).toHaveElement('#core-basics-section-forum');
    expect(page.first('#core-basics-section-forum').textContent).toContain('Acme tagline');
  });

  test('a setting registered on Basics without a section joins the section shown before it', () => {
    const page = mq(BasicsPage);
    const location = app.registry.getSettingLocation('core-basics', { setting: 'acme_unsectioned' } as any);

    expect(location.section?.id).toBe('home');
    expect(page.first('#core-basics-section-home').textContent).toContain('Acme unsectioned');
  });

  test('FormSections, including ones added by extensions, render as SettingsSections', () => {
    const page = mq(AdvancedPage);

    expect(page).toHaveElement('.FormSection.SettingsSection .SettingsSection-title');
    expect(page.find('.FormSection').some((el: Element) => el.textContent!.includes('acme body'))).toBe(true);
  });

  test('BasicsPage.content() still returns an array for extensions that push onto it', () => {
    const page = new BasicsPage();

    expect(Array.isArray(page.content())).toBe(true);
  });
});
