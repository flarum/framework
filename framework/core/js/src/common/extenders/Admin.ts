import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInternal } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import type Mithril from 'mithril';
import type { GeneralIndexItem } from '../../admin/states/GeneralSearchIndex';

export default class Admin implements IExtender<AdminApplication> {
  protected settings: { setting?: () => SettingConfigInternal | null; customSetting?: () => Mithril.Children; priority: number }[] = [];
  protected permissions: { permission: () => PermissionConfig | null; type: PermissionType; priority: number }[] = [];
  protected customPage: CustomExtensionPage | null = null;
  protected generalIndexes: { settings?: () => GeneralIndexItem[]; permissions?: () => GeneralIndexItem[] } = {};

  /**
   * Register a setting to be shown on the extension's settings page.
   */
  setting(setting: () => SettingConfigInternal | null, priority = 0) {
    this.settings.push({ setting, priority });

    return this;
  }

  /**
   * Register a custom setting to be shown on the extension's settings page.
   */
  customSetting(setting: () => Mithril.Children, priority = 0) {
    this.settings.push({ customSetting: setting, priority });

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
    app.registry.for(extension.name);

    this.settings.forEach(({ setting, customSetting, priority }) => {
      const settingConfig = setting ? setting() : customSetting!;

      if (settingConfig) {
        app.registry.registerSetting(settingConfig, priority);
      }
    });

    this.permissions.forEach(({ permission, type, priority }) => {
      const permissionConfig = permission();

      if (permissionConfig) {
        app.registry.registerPermission(permissionConfig, type, priority);
      }
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
  }
}
