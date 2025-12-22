import IExtender, { IExtensionModule } from './IExtender';
import type AdminApplication from '../../admin/AdminApplication';
import type { CustomExtensionPage, SettingConfigInput } from '../../admin/utils/AdminRegistry';
import type { PermissionConfig, PermissionType } from '../../admin/components/PermissionGrid';
import type Mithril from 'mithril';
import type { GeneralIndexItem } from '../../admin/states/GeneralSearchIndex';
export default class Admin implements IExtender<AdminApplication> {
    protected context: string | null;
    protected settings: {
        setting?: () => SettingConfigInput | null;
        customSetting?: () => Mithril.Children;
        priority: number;
    }[];
    protected settingReplacements: {
        setting: string;
        replacement: (original: SettingConfigInput) => SettingConfigInput;
    }[];
    protected settingPriorityChanges: {
        setting: string;
        priority: number;
    }[];
    protected settingRemovals: string[];
    protected permissions: {
        permission: () => PermissionConfig | null;
        type: PermissionType;
        priority: number;
    }[];
    protected permissionsReplacements: {
        permission: string;
        type: PermissionType;
        replacement: (original: PermissionConfig) => PermissionConfig;
    }[];
    protected permissionsPriorityChanges: {
        permission: string;
        type: PermissionType;
        priority: number;
    }[];
    protected permissionsRemovals: {
        permission: string;
        type: PermissionType;
    }[];
    protected customPage: CustomExtensionPage | null;
    protected generalIndexes: {
        settings?: () => GeneralIndexItem[];
        permissions?: () => GeneralIndexItem[];
    };
    constructor(context?: string | null);
    /**
     * Register a setting to be shown on the extension's settings page.
     */
    setting(setting: () => SettingConfigInput | null, priority?: number): this;
    /**
     * Replace an existing setting's configuration.
     */
    replaceSetting(setting: string, replacement: (original: SettingConfigInput) => SettingConfigInput): this;
    /**
     * Change the priority of an existing setting.
     */
    setSettingPriority(setting: string, priority: number): this;
    /**
     * Remove a setting from the extension's settings page.
     */
    removeSetting(setting: string): this;
    /**
     * Register a custom setting to be shown on the extension's settings page.
     */
    customSetting(setting: () => Mithril.Children, priority?: number): this;
    /**
     * Register a permission to be shown on the extension's permissions page.
     */
    permission(permission: () => PermissionConfig | null, type: PermissionType, priority?: number): this;
    /**
     * Replace an existing permission's configuration.
     */
    replacePermission(permission: string, replacement: (original: PermissionConfig) => PermissionConfig, type: PermissionType): this;
    /**
     * Change the priority of an existing permission.
     */
    setPermissionPriority(permission: string, type: PermissionType, priority: number): this;
    /**
     * Remove a permission from the extension's permissions page.
     */
    removePermission(permission: string, type: PermissionType): this;
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
