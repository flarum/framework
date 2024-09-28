import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInternal } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import Mithril from 'mithril';

export default class Admin implements IExtender<AdminApplication> {
  protected settings: { setting?: () => SettingConfigInternal; customSetting?: () => Mithril.Children; priority: number }[] = [];
  protected permissions: { permission: () => PermissionConfig; type: PermissionType; priority: number }[] = [];
  protected customPage: CustomExtensionPage | null = null;

  /**
   * Register a setting to be shown on the extension's settings page.
   */
  setting(setting: () => SettingConfigInternal, priority = 0) {
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
  permission(permission: () => PermissionConfig, type: PermissionType, priority = 0) {
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

  extend(app: AdminApplication, extension: IExtensionModule) {
    app.registry.for(extension.name);

    this.settings.forEach(({ setting, customSetting, priority }) => {
      app.registry.registerSetting(setting ? setting() : customSetting!, priority);
    });

    this.permissions.forEach(({ permission, type, priority }) => {
      app.registry.registerPermission(permission(), type, priority);
    });

    if (this.customPage) {
      app.registry.registerPage(this.customPage);
    }
  }
}
