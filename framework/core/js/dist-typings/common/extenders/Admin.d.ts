import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInternal } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import Mithril from 'mithril';
export default class Admin implements IExtender<AdminApplication> {
    protected settings: {
        setting?: () => SettingConfigInternal;
        customSetting?: () => Mithril.Children;
        priority: number;
    }[];
    protected permissions: {
        permission: () => PermissionConfig;
        type: PermissionType;
        priority: number;
    }[];
    protected customPage: CustomExtensionPage | null;
    /**
     * Register a setting to be shown on the extension's settings page.
     */
    setting(setting: () => SettingConfigInternal, priority?: number): this;
    /**
     * Register a custom setting to be shown on the extension's settings page.
     */
    customSetting(setting: () => Mithril.Children, priority?: number): this;
    /**
     * Register a permission to be shown on the extension's permissions page.
     */
    permission(permission: () => PermissionConfig, type: PermissionType, priority?: number): this;
    /**
     * Register a custom page to be shown in the admin interface.
     */
    page(page: CustomExtensionPage): this;
    extend(app: AdminApplication, extension: IExtensionModule): void;
}
