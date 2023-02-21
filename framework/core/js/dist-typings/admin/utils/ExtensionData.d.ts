import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import { SettingsComponentOptions } from '../components/AdminPage';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';
import { PermissionConfig, PermissionType } from '../components/PermissionGrid';
declare type SettingConfigInput = SettingsComponentOptions | (() => Mithril.Children);
declare type SettingConfigInternal = SettingsComponentOptions | ((() => Mithril.Children) & {
    setting: string;
});
export declare type CustomExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> = new () => ExtensionPage<Attrs>;
declare type ExtensionConfig = {
    settings?: ItemList<SettingConfigInternal>;
    permissions?: {
        view?: ItemList<PermissionConfig>;
        start?: ItemList<PermissionConfig>;
        reply?: ItemList<PermissionConfig>;
        moderate?: ItemList<PermissionConfig>;
    };
    page?: CustomExtensionPage;
};
declare type InnerDataNoActiveExtension = {
    currentExtension: null;
    data: {
        [key: string]: ExtensionConfig | undefined;
    };
};
declare type InnerDataActiveExtension = {
    currentExtension: string;
    data: {
        [key: string]: ExtensionConfig;
    };
};
export default class ExtensionData {
    protected state: InnerDataActiveExtension | InnerDataNoActiveExtension;
    /**
     * This function simply takes the extension id
     *
     * @example
     * app.extensionData.for('flarum-tags')
     *
     * flarum/flags -> flarum-flags | acme/extension -> acme-extension
     */
    for(extension: string): this;
    /**
     * This function registers your settings with Flarum
     *
     * It takes either a settings object or a callback.
     *
     * @example
     *
     * .registerSetting({
     *   setting: 'flarum-flags.guidelines_url',
     *   type: 'text', // This will be inputted into the input tag for the setting (text/number/etc)
     *   label: app.translator.trans('flarum-flags.admin.settings.guidelines_url_label')
     * }, 15) // priority is optional (ItemList)
     */
    registerSetting(content: SettingConfigInput, priority?: number): this;
    /**
     * This function registers your permission with Flarum
     *
     * @example
     *
     * .registerPermission('permissions', {
     *     icon: 'fas fa-flag',
     *     label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
     *     permission: 'discussion.viewFlags'
     * }, 'moderate', 65)
     */
    registerPermission(content: PermissionConfig, permissionType: PermissionType, priority?: number): this;
    /**
     * Replace the default extension page with a custom component.
     * This component would typically extend ExtensionPage
     */
    registerPage(component: CustomExtensionPage): this;
    /**
     * Get an extension's registered settings
     */
    getSettings(extensionId: string): SettingConfigInternal[] | undefined;
    /**
     * Get an ItemList of all extensions' registered permissions
     */
    getAllExtensionPermissions(type: PermissionType): ItemList<PermissionConfig>;
    /**
     * Get a singular extension's registered permissions
     */
    getExtensionPermissions(extension: string, type: PermissionType): ItemList<PermissionConfig>;
    /**
     * Checks whether a given extension has registered permissions.
     */
    extensionHasPermissions(extension: string): boolean;
    /**
     * Returns an extension's custom page component if it exists.
     */
    getPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs>(extension: string): CustomExtensionPage<Attrs> | undefined;
}
export {};
