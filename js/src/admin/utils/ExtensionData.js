import ItemList from '../../common/utils/ItemList';

export default class ExtensionData {
  constructor() {
    this.data = {};
    this.currentExtension = null;
  }

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
  load(extension) {
    this.currentExtension = extension;
    this.data[extension] = this.data[extension] || {};

    return this;
  }

  /**
   * This function registers your settings with Flarum
   *
   * @example - settings
   *
   * .registerSettings('settings', {
   *     'flarum-flags.guidelines_url': {
   *         type: 'text', // This will be inputted into the input tag for the setting (text/number/etc)
   *         label: app.translator.trans('flarum-flags.admin.settings.guidelines_url_label')
   *     }
   * })
   *
   *
   * @param type
   * @param content
   * @param permissionType
   * @param priority
   * @returns {ExtensionData}
   */
  registerSettings(content, permissionType = null, priority = 0) {
    this.data[this.currentExtension].settings = this.data[this.currentExtension].settings || {};

    this.data[this.currentExtension].settings = content;

    return this;
  }

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
  registerPermission(content, permissionType = null, priority = 0) {
    this.data[this.currentExtension].permissions = this.data[this.currentExtension].permissions || {};

    if (!this.data[this.currentExtension].permissions[permissionType]) {
      this.data[this.currentExtension].permissions[permissionType] = new ItemList();
    }

    this.data[this.currentExtension].permissions[permissionType].add(content.permission, content);

    return this;
  }

  /**
   * Replace the default extension page with a custom component
   *
   * @param component
   * @returns {ExtensionData}
   */
  registerPage(component) {
    this.data[this.currentExtension].page = component;

    return this;
  }

  /**
   * Get an extension's registered settings
   *
   * @param extensionId
   * @returns {boolean|*}
   */
  getSettings(extensionId) {
    if (this.data[extensionId] && this.data[extensionId].settings) {
      return this.data[extensionId].settings;
    }

    return false;
  }

  /**
   *
   * Get an ItemList of all extensions' registered permissions
   *
   * @param extension
   * @param type
   * @returns {ItemList}
   */
  getPermissions(type) {
    const items = new ItemList();

    Object.keys(this.data).map((extension) => {
      if (this.data[extension] && this.data[extension].permissions && this.data[extension].permissions[type]) {
        items.merge(this.data[extension].permissions[type]);
      }
    });

    return items;
  }

  /**
   * Get a singular extension's registered permissions
   *
   * @param extension
   * @param type
   * @returns {boolean|*}
   */
  getExtensionPermissions(extension, type) {
    if (this.data[extension] && this.data[extension].permissions) {
      if (!type) {
        return true;
      } else if (this.data[extension].permissions[type]) {
        return this.data[extension].permissions[type];
      }
    }

    return false;
  }

  /**
   * Returns an extension's custom page component if it exists.
   *
   * @param extension
   * @returns {boolean|*}
   */
  getPage(extension) {
    if (this.data[extension]) {
      return this.data[extension].page;
    }

    return false;
  }
}
