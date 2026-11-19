import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInput, SettingsSectionConfig, SettingsTabConfig } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import type Mithril from 'mithril';
import type { GeneralIndexItem } from '../../admin/states/GeneralSearchIndex';

export default class Admin implements IExtender<AdminApplication> {
  protected context: string | null;

  protected settings: {
    setting?: () => SettingConfigInput | null;
    customSetting?: () => Mithril.Children;
    section?: string;
    priority: number;
  }[] = [];
  protected sections: { section: () => SettingsSectionConfig | null; priority: number }[] = [];
  protected tabs: { tab: () => SettingsTabConfig | null; priority: number }[] = [];
  protected settingReplacements: { setting: string; replacement: (original: SettingConfigInput) => SettingConfigInput }[] = [];
  protected settingPriorityChanges: { setting: string; priority: number }[] = [];
  protected settingRemovals: string[] = [];
  protected permissions: { permission: () => PermissionConfig | null; type: PermissionType; priority: number }[] = [];
  protected permissionsReplacements: { permission: string; type: PermissionType; replacement: (original: PermissionConfig) => PermissionConfig }[] =
    [];
  protected permissionsPriorityChanges: { permission: string; type: PermissionType; priority: number }[] = [];
  protected permissionsRemovals: { permission: string; type: PermissionType }[] = [];
  protected customPage: CustomExtensionPage | null = null;
  protected generalIndexes: { settings?: () => GeneralIndexItem[]; permissions?: () => GeneralIndexItem[] } = {};

  constructor(context: string | null = null) {
    this.context = context;
  }

  /**
   * Register a setting to be shown on the extension's settings page.
   */
  setting(setting: () => SettingConfigInput | null, priority = 0) {
    this.settings.push({ setting, priority });

    return this;
  }

  /**
   * Replace an existing setting's configuration.
   */
  replaceSetting(setting: string, replacement: (original: SettingConfigInput) => SettingConfigInput) {
    this.settingReplacements.push({ setting, replacement });

    return this;
  }

  /**
   * Change the priority of an existing setting.
   */
  setSettingPriority(setting: string, priority: number) {
    this.settingPriorityChanges.push({ setting, priority });

    return this;
  }

  /**
   * Remove a setting from the extension's settings page.
   */
  removeSetting(setting: string) {
    this.settingRemovals.push(setting);

    return this;
  }

  /**
   * Register a custom setting to be shown on the extension's settings page.
   *
   * Pass a section id to show it inside that section.
   */
  customSetting(setting: () => Mithril.Children, priority = 0, section?: string) {
    this.settings.push({ customSetting: setting, priority, section });

    return this;
  }

  /**
   * Register a titled group of settings on the extension's settings page.
   * Settings join it with `section: '<id>'`.
   *
   * @example
   * new Extend.Admin()
   *   .section(() => ({ id: 'display', label: app.translator.trans('acme-foo.admin.sections.display') }))
   *   .setting(() => ({ setting: 'acme-foo.compact', type: 'bool', label: '...', section: 'display' }))
   */
  section(section: () => SettingsSectionConfig | null, priority = 0) {
    this.sections.push({ section, priority });

    return this;
  }

  /**
   * Register a tab on the extension's settings page. Sections join it with `tab: '<id>'`.
   *
   * Once a page has a tab, its permissions are shown in a tab of their own.
   */
  tab(tab: () => SettingsTabConfig | null, priority = 0) {
    this.tabs.push({ tab, priority });

    return this;
  }

  /**
   * Register a permission to be shown on the extension's permissions page.
   */
  permission(permission: () => PermissionConfig | null, type: PermissionType, priority = 0) {
    this.permissions.push({ permission, type, priority });

    return this;
  }

  /**
   * Replace an existing permission's configuration.
   */
  replacePermission(permission: string, replacement: (original: PermissionConfig) => PermissionConfig, type: PermissionType) {
    this.permissionsReplacements.push({ permission, type, replacement });

    return this;
  }

  /**
   * Change the priority of an existing permission.
   */
  setPermissionPriority(permission: string, type: PermissionType, priority: number) {
    this.permissionsPriorityChanges.push({ permission, type, priority });

    return this;
  }

  /**
   * Remove a permission from the extension's permissions page.
   */
  removePermission(permission: string, type: PermissionType) {
    this.permissionsRemovals.push({ permission, type });

    return this;
  }

  /**
   * Register a custom page to be shown in the admin interface.
   */
  page(page: CustomExtensionPage) {
    this.customPage = page;

    return this;
  }

  /**
   * Register a custom general search index entry.
   */
  generalIndexItems(type: 'settings' | 'permissions', items: () => GeneralIndexItem[]) {
    this.generalIndexes[type] = items;

    return this;
  }

  extend(app: AdminApplication, extension: IExtensionModule) {
    app.beforeMount(() => {
      app.registry.for(this.context || extension.name);

      this.tabs.forEach(({ tab, priority }) => {
        const tabConfig = tab();

        if (tabConfig) {
          app.registry.registerTab(tabConfig, priority);
        }
      });

      this.sections.forEach(({ section, priority }) => {
        const sectionConfig = section();

        if (sectionConfig) {
          app.registry.registerSection(sectionConfig, priority);
        }
      });

      this.settings.forEach(({ setting, customSetting, section, priority }) => {
        const settingConfig = setting ? setting() : customSetting && section ? Object.assign(customSetting, { section }) : customSetting!;

        if (settingConfig) {
          app.registry.registerSetting(settingConfig, priority);
        }
      });

      this.settingReplacements.forEach(({ setting, replacement }) => {
        app.registry.setSetting(setting, replacement);
      });

      this.settingPriorityChanges.forEach(({ setting, priority }) => {
        app.registry.setSettingPriority(setting, priority);
      });

      this.settingRemovals.forEach((setting) => {
        app.registry.removeSetting(setting);
      });

      this.permissions.forEach(({ permission, type, priority }) => {
        const permissionConfig = permission();

        if (permissionConfig) {
          app.registry.registerPermission(permissionConfig, type, priority);
        }
      });

      this.permissionsReplacements.forEach(({ permission, type, replacement }) => {
        app.registry.setPermission(permission, replacement, type);
      });

      this.permissionsPriorityChanges.forEach(({ permission, type, priority }) => {
        app.registry.setPermissionPriority(permission, type, priority);
      });

      this.permissionsRemovals.forEach(({ permission, type }) => {
        app.registry.removePermission(permission, type);
      });

      if (this.customPage) {
        app.registry.registerPage(this.customPage);
      }

      app.generalIndex.for(extension.name);

      Object.keys(this.generalIndexes).forEach((key) => {
        if (key !== 'settings' && key !== 'permissions') return;

        const callback = this.generalIndexes[key];

        if (callback) {
          app.generalIndex.add(key, callback());
        }
      });
    });
  }
}
