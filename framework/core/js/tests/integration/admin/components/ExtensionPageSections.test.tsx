import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import ExtensionPage from '../../../../src/admin/components/ExtensionPage';
import GeneralSearchSource from '../../../../src/admin/components/GeneralSearchSource';
import Admin from '../../../../src/common/extenders/Admin';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';
import type ItemList from '../../../../src/common/utils/ItemList';

const extension = (id: string) => ({
  id,
  version: '1.0.0',
  description: '',
  icon: { name: 'fas fa-cog' },
  links: {},
  extra: { 'flarum-extension': { title: id } },
});

beforeAll(() =>
  bootstrapAdmin({
    extensions: {
      'acme-flat': extension('acme-flat'),
      'acme-sections': extension('acme-sections'),
      'acme-tabs': extension('acme-tabs'),
      'acme-perms': extension('acme-perms'),
      'acme-both': extension('acme-both'),
      'acme-empty': extension('acme-empty'),
      'acme-custom': extension('acme-custom'),
    },
    settings: {
      extensions_enabled: JSON.stringify(['acme-flat', 'acme-sections', 'acme-tabs', 'acme-perms', 'acme-both', 'acme-empty', 'acme-custom']),
    },
    dbDriver: 'sqlite',
  })
);

describe('ExtensionPage settings layout', () => {
  beforeAll(() => {
    new Admin('acme-flat')
      .setting(() => ({ setting: 'acme-flat.one', type: 'text', label: 'Flat one' }))
      .extend(app, { name: 'acme-flat', exports: {} });

    new Admin('acme-sections')
      .section(() => ({ id: 'second', label: 'Second section' }), 10)
      .section(() => ({ id: 'first', label: 'First section', description: 'About the first', icon: 'fas fa-star' }), 20)
      .section(() => ({ id: 'empty', label: 'Empty section' }))
      .setting(() => ({ setting: 'acme-sections.loose', type: 'text', label: 'Loose setting' }))
      .setting(() => ({ setting: 'acme-sections.a', type: 'text', label: 'Setting A', section: 'first' }))
      .setting(() => ({ setting: 'acme-sections.b', type: 'text', label: 'Setting B', section: 'second' }))
      .customSetting(() => <p className="custom-in-second">Custom</p>, 0, 'second')
      .extend(app, { name: 'acme-sections', exports: {} });

    new Admin('acme-tabs')
      .tab(() => ({ id: 'general', label: 'General' }), 20)
      .tab(() => ({ id: 'advanced', label: 'Advanced', icon: 'fas fa-cogs' }), 10)
      .section(() => ({ id: 'basics', label: 'Basics' }))
      .section(() => ({ id: 'tuning', label: 'Tuning', tab: 'advanced' }))
      .setting(() => ({ setting: 'acme-tabs.basic', type: 'text', label: 'Basic setting', section: 'basics' }))
      .setting(() => ({ setting: 'acme-tabs.tuned', type: 'text', label: 'Tuned setting', section: 'tuning' }))
      .extend(app, { name: 'acme-tabs', exports: {} });

    new Admin('acme-perms')
      .permission(() => ({ icon: 'fas fa-eye', label: 'View things', permission: 'acme.view' }), 'view')
      .extend(app, { name: 'acme-perms', exports: {} });

    new Admin('acme-both')
      .setting(() => ({ setting: 'acme-both.one', type: 'text', label: 'Both one' }))
      .permission(() => ({ icon: 'fas fa-eye', label: 'View both', permission: 'acme.viewBoth' }), 'view')
      .extend(app, { name: 'acme-both', exports: {} });

    new Admin('acme-empty').extend(app, { name: 'acme-empty', exports: {} });

    new Admin('acme-custom')
      .setting(() => ({ setting: 'acme-custom.one', type: 'text', label: 'Custom one' }))
      .permission(() => ({ icon: 'fas fa-eye', label: 'View custom', permission: 'acme.viewCustom' }), 'view')
      .extend(app, { name: 'acme-custom', exports: {} });

    app.boot();
  });

  test('settings without sections or permissions: flat form, no tab bar, no permissions block', () => {
    const page = mq(ExtensionPage, { id: 'acme-flat' });

    expect(page).toContainRaw('Flat one');
    expect(page).not.toHaveElement('.SettingsSection');
    expect(page).not.toHaveElement('.AdminPage-settingsForm--sectioned');
    expect(page).not.toHaveElement('[role=tablist]');
    expect(page).not.toHaveElement('.ExtensionPage-permissions');
  });

  test('settings and permissions: a General tab and a Permissions tab', () => {
    const page = mq(ExtensionPage, { id: 'acme-both' });
    const tabs = page.find('[role=tab]').map((el: Element) => el.getAttribute('data-tab'));

    expect(tabs).toEqual(['general', 'permissions']);
    expect(page).toContainRaw('Both one');
    expect(page).not.toContainRaw('View both');
  });

  test('the Permissions tab opens from ?tab=permissions', () => {
    const page = mq(ExtensionPage, { id: 'acme-both', tab: 'permissions' });

    expect(page).toHaveElement('.Tabs-content .ExtensionPermissionGrid, .Tabs-content .PermissionGrid');
    expect(page).not.toContainRaw('Both one');
  });

  test('permissions only: the permissions block without a tab bar', () => {
    const page = mq(ExtensionPage, { id: 'acme-perms' });

    expect(page).not.toHaveElement('[role=tablist]');
    expect(page).toHaveElement('.ExtensionPage-permissions-header');
    expect(page).not.toContainRaw('This extension has no settings');
  });

  test('neither settings nor permissions: one message', () => {
    const page = mq(ExtensionPage, { id: 'acme-empty' });

    expect(page).toHaveElement('.ExtensionPage-empty');
    expect(page).not.toHaveElement('[role=tablist]');
  });

  test('sections() keeps its content and permissions keys for extensions that reorder them', () => {
    const page = new ExtensionPage();
    page.extension = app.data.extensions['acme-both'];
    const items = page.sections({} as any);

    expect(items.has('content')).toBe(true);
    expect(items.has('permissions')).toBe(true);
    expect(() => items.setPriority('permissions', 5)).not.toThrow();
  });

  test('a custom page that replaces content() keeps the stacked layout', () => {
    class LegacyPage extends ExtensionPage {
      content() {
        return <p className="legacy-content">Legacy</p>;
      }
    }

    const page = mq(LegacyPage, { id: 'acme-custom' });

    expect(page).toHaveElement('.legacy-content');
    expect(page).toHaveElement('.ExtensionPage-permissions-header');
    expect(page).not.toHaveElement('[role=tablist]');
  });

  test('a custom page with its own tabItems(), tabs() and activeTab is left alone', () => {
    class OwnTabsPage extends ExtensionPage {
      activeTab = 'mine';

      tabItems() {
        return [{ id: 'mine', label: 'Mine' }];
      }

      tabs() {
        return this.tabItems().map((tab) => <span className="own-tab">{tab.label}</span>);
      }

      content() {
        return <div className="own-content">{this.tabs()}</div>;
      }
    }

    const page = mq(OwnTabsPage, { id: 'acme-custom' });

    expect(page).toHaveElement('.own-content .own-tab');
    expect(page).toHaveElement('.ExtensionPage-permissions-header');
    expect(page).not.toHaveElement('[role=tablist]');
  });

  test('a custom page opts into tabs by overriding pageTabItems()', () => {
    class TabbedPage extends ExtensionPage {
      content() {
        return <p className="legacy-content">Legacy</p>;
      }

      pageTabItems() {
        const items = super.pageTabItems();
        items.add('log', { id: 'log', label: 'Log' }, 100);
        return items;
      }

      pageTabContent(tab: string, vnode: any) {
        return tab === 'log' ? <p className="log-content">Log</p> : super.pageTabContent(tab, vnode);
      }
    }

    const page = mq(TabbedPage, { id: 'acme-custom' });
    const tabs = page.find('[role=tab]').map((el: Element) => el.getAttribute('data-tab'));

    expect(tabs).toEqual(['log', 'general', 'permissions']);
    expect(page).toHaveElement('.log-content');
    expect(page).not.toHaveElement('.legacy-content');
  });

  test('a patch to ExtensionPage.prototype.content shows in the General tab, which keeps its tabs', () => {
    const original = ExtensionPage.prototype.content;
    ExtensionPage.prototype.content = function (this: ExtensionPage, vnode: any) {
      return [original.call(this, vnode), <p className="patched-notice">Notice</p>];
    };

    try {
      const tabbed = mq(ExtensionPage, { id: 'acme-both' });
      expect(tabbed).toHaveElement('[role=tablist]');
      expect(tabbed).toHaveElement('.Tabs-content .patched-notice');

      const single = mq(ExtensionPage, { id: 'acme-flat' });
      expect(single).toHaveElement('.patched-notice');
      expect(single).toHaveElement('.ExtensionPage-settings > .container .Form');
    } finally {
      ExtensionPage.prototype.content = original;
    }
  });

  describe('changes other extensions make to sections()', () => {
    const withSections = (change: (items: ItemList<any>) => void) => {
      const original = ExtensionPage.prototype.sections;
      ExtensionPage.prototype.sections = function (this: ExtensionPage, vnode: any) {
        const items = original.call(this, vnode);
        change(items);
        return items;
      };

      try {
        return mq(ExtensionPage, { id: 'acme-both' });
      } finally {
        ExtensionPage.prototype.sections = original;
      }
    };

    test('replacing content brings the permissions block back below it', () => {
      const page = withSections((items) => items.setContent('content', <p className="own-content">Own</p>));

      expect(page).toHaveElement('.own-content');
      expect(page).toHaveElement('.ExtensionPage-permissions-header');
      expect(page).not.toHaveElement('[role=tablist]');
    });

    test('removing permissions drops the Permissions tab', () => {
      const page = withSections((items) => items.remove('permissions'));

      expect(page).not.toHaveElement('[role=tablist]');
      expect(page).not.toHaveElement('.ExtensionPermissionGrid, .PermissionGrid');
      expect(page).toContainRaw('Both one');
    });

    test('replacing permissions shows the replacement instead of the tab', () => {
      const page = withSections((items) => items.setContent('permissions', <p className="own-permissions">Mine</p>));

      expect(page).toHaveElement('.own-permissions');
      expect(page).not.toHaveElement('[role=tab][data-tab=permissions]');
    });

    test('reordering keeps both', () => {
      const page = withSections((items) => items.setPriority('permissions', 100));

      expect(page).toHaveElement('[role=tab][data-tab=permissions]');
    });
  });

  test('a custom page with its own permissionsContent() method is not called by core', () => {
    class HelperPage extends ExtensionPage {
      content() {
        return <p className="legacy-content">Legacy</p>;
      }

      permissionsContent() {
        return <p className="own-helper">Own helper</p>;
      }
    }

    const page = mq(HelperPage, { id: 'acme-custom' });

    expect(page).not.toHaveElement('.own-helper');
    expect(page).toHaveElement('.ExtensionPage-permissions-header');
  });

  test('replaceSetting() keeps the setting in its section', () => {
    app.registry.for('acme-sections').setSetting('acme-sections.a', () => ({ setting: 'acme-sections.a', type: 'text', label: 'Setting A2' }));

    const page = mq(ExtensionPage, { id: 'acme-sections' });

    expect(page.first('#acme-sections-section-first').textContent).toContain('Setting A2');
  });

  test('unsaved changes mark the tab their setting is on', () => {
    const page = new ExtensionPage();
    page.extension = app.data.extensions['acme-tabs'];

    page.setting('acme-tabs.tuned')('changed');
    expect([...page.unsavedTabs()]).toEqual(['advanced']);
  });

  test('sections render in priority order with their settings, and empty ones are skipped', () => {
    const page = mq(ExtensionPage, { id: 'acme-sections' });
    const titles = page.find('.SettingsSection-title').map((el: Element) => el.textContent);

    expect(titles).toEqual(['First section', 'Second section']);
    expect(page).toHaveElement('#acme-sections-section-first .SettingsSection-icon');
    expect(page).toContainRaw('About the first');
    expect(page.first('#acme-sections-section-first').textContent).toContain('Setting A');
    expect(page.first('#acme-sections-section-second').textContent).toContain('Setting B');
    expect(page).toHaveElement('#acme-sections-section-second .custom-in-second');
  });

  test('settings without a section are shown ungrouped before the sections', () => {
    const page = mq(ExtensionPage, { id: 'acme-sections' });
    const body = page.first('.AdminPage-settingsForm--sectioned .Form-body');

    expect(body.firstElementChild!.className).toBe('AdminPage-ungrouped');
    expect(body.firstElementChild!.textContent).toContain('Loose setting');
  });

  test('registered tabs show only their own sections; no Permissions tab without permissions', () => {
    const page = mq(ExtensionPage, { id: 'acme-tabs' });
    const tabs = page.find('[role=tab]').map((el: Element) => el.getAttribute('data-tab'));

    expect(tabs).toEqual(['general', 'advanced']);
    expect(page.first('[role=tab][data-tab=general]').getAttribute('aria-selected')).toBe('true');
    expect(page).toContainRaw('Basic setting');
    expect(page).not.toContainRaw('Tuned setting');
    expect(page).not.toHaveElement('.ExtensionPage-permissions-header');
  });

  test('the ?tab= attribute opens that tab', () => {
    const page = mq(ExtensionPage, { id: 'acme-tabs', tab: 'advanced' });

    expect(page.first('[role=tab][data-tab=advanced]').getAttribute('aria-selected')).toBe('true');
    expect(page).toContainRaw('Tuned setting');
    expect(page).not.toContainRaw('Basic setting');
  });

  test('an unknown tab falls back to the first tab', () => {
    const page = mq(ExtensionPage, { id: 'acme-tabs', tab: 'nope' });

    expect(page.first('[role=tab][data-tab=general]').getAttribute('aria-selected')).toBe('true');
  });

  test('the registry locates a setting in its tab and section', () => {
    const tuned = app.registry.getSettings('acme-tabs')!.find((s) => 'setting' in s && s.setting === 'acme-tabs.tuned')!;
    const location = app.registry.getSettingLocation('acme-tabs', tuned);

    expect(location.tab?.id).toBe('advanced');
    expect(location.section?.id).toBe('tuning');
  });

  test('admin search shows the tab and section of a setting and links to its tab', async () => {
    const source = new GeneralSearchSource();
    await source.search('tuned', 10);

    const result = (source['results'].get('tuned') ?? []).find((r) => r.id.startsWith('acme-tabs'));

    expect(result!.tree).toEqual(['Advanced', 'Tuning', 'Tuned setting']);
    expect(result!.link).toContain('tab=advanced');
  });
});
