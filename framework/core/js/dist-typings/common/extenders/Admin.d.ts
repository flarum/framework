import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInternal } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import type Mithril from 'mithril';
import type { GeneralIndexItem } from '../../admin/states/GeneralSearchIndex';
export default class Admin implements IExtender<AdminApplication> {
    protected settings: {
        setting?: () => SettingConfigInternal | null;
        customSetting?: () => Mithril.Children;
        priority: number;
    }[];
    protected permissions: {
        permission: () => PermissionConfig | null;
        type: PermissionType;
        priority: number;
    }[];
    protected customPage: CustomExtensionPage | null;
    protected generalIndexes: {
        settings?: () => GeneralIndexItem[];
        permissions?: () => GeneralIndexItem[];
    };
    /**
     * Register a setting to be shown on the extension's settings page.
     */
    setting(setting: () => SettingConfigInternal | null, priority?: number): this;
    /**
     * Register a custom setting to be shown on the extension's settings page.
     */
    customSetting(setting: () => Mithril.Children, priority?: number): this;
    /**
     * Register a permission to be shown on the extension's permissions page.
     */
    permission(permission: () => PermissionConfig | null, type: PermissionType, priority?: number): this;
    /**
     * Register a custom page to be shown in the admin interface.
     */
    page(page: CustomExtensionPage): this;
    /**
     * Register a custom general search index entry.
     */
    generalIndexItems(type: 'settings' | 'permissions', items: () => GeneralIndexItem[]): this;
    extend(app: AdminApplication, extension: IExtensionModule): void;
}
