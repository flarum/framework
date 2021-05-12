export default class ExtensionData {
    data: {};
    currentExtension: any;
    /**
     * This function simply takes the extension id
     *
     * @example
     * app.extensionData.load('flarum-tags')
     *
     * flarum/flags -> flarum-flags | acme/extension -> acme-extension
     *
     * @param extension
     */
    for(extension: any): ExtensionData;
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
     *
     *
     * @param content
     * @param priority
     * @returns {ExtensionData}
     */
    registerSetting(content: any, priority?: number): ExtensionData;
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
     *
     * @param content
     * @param permissionType
     * @param priority
     * @returns {ExtensionData}
     */
    registerPermission(content: any, permissionType?: any, priority?: number): ExtensionData;
    /**
     * Replace the default extension page with a custom component.
     * This component would typically extend ExtensionPage
     *
     * @param component
     * @returns {ExtensionData}
     */
    registerPage(component: any): ExtensionData;
    /**
     * Get an extension's registered settings
     *
     * @param extensionId
     * @returns {boolean|*}
     */
    getSettings(extensionId: any): boolean | any;
    /**
     *
     * Get an ItemList of all extensions' registered permissions
     *
     * @param extension
     * @param type
     * @returns {ItemList}
     */
    getAllExtensionPermissions(type: any): ItemList;
    /**
     * Get a singular extension's registered permissions
     *
     * @param extension
     * @param type
     * @returns {boolean|*}
     */
    getExtensionPermissions(extension: any, type: any): boolean | any;
    /**
     * Checks whether a given extension has registered permissions.
     *
     * @param extension
     * @returns {boolean}
     */
    extensionHasPermissions(extension: any): boolean;
    /**
     * Returns an extension's custom page component if it exists.
     *
     * @param extension
     * @returns {boolean|*}
     */
    getPage(extension: any): boolean | any;
}
import ItemList from "../../common/utils/ItemList";
