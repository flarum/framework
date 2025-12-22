import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import { SettingsComponentOptions } from '../components/AdminPage';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';
import type { PermissionConfig, PermissionType } from '../components/PermissionGrid';

export type SettingConfigInput = SettingsComponentOptions | (() => Mithril.Children);

export type SettingConfigInternal = SettingsComponentOptions | ((() => Mithril.Children) & { setting: string });

export type CustomExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> = new () => ExtensionPage<Attrs>;

export type ExtensionConfig = {
  settings?: ItemList<SettingConfigInternal>;
  permissions?: {
    view?: ItemList<PermissionConfig>;
    start?: ItemList<PermissionConfig>;
    reply?: ItemList<PermissionConfig>;
    moderate?: ItemList<PermissionConfig>;
  };
  page?: CustomExtensionPage;
};

type InnerDataNoActiveExtension = {
  currentExtension: null;
  data: {
    [key: string]: ExtensionConfig | undefined;
  };
};

type InnerDataActiveExtension = {
  currentExtension: string;
  data: {
    [key: string]: ExtensionConfig;
  };
};

const noActiveExtensionErrorMessage = 'You must select an active extension via `.for()` before using extensionData.';

export default class AdminRegistry {
  protected state: InnerDataActiveExtension | InnerDataNoActiveExtension = {
    currentExtension: null,
    data: {},
  };

  /**
   * This function simply takes the extension id
   *
   * @example
   * app.registry.for('flarum-tags')
   *
   * flarum/flags -> flarum-flags | acme/extension -> acme-extension
   */
  for(extension: string) {
    this.state.currentExtension = extension;
    this.state.data[extension] = this.state.data[extension] || {};

    return this;
  }

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
  registerSetting(content: SettingConfigInput, priority = 0, key: string | null = null): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const tmpContent = content as SettingConfigInternal;

    // Callbacks can be passed in instead of settings to display custom content.
    // By default, they will be added with the `null` key, since they don't have a `.setting` attr.
    // To support multiple such items for one extension, we assign a random ID.
    // 36 is arbitrary length, but makes collisions very unlikely.
    if (tmpContent instanceof Function) {
      tmpContent.setting = key || Math.random().toString(36);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();
    settings.add(tmpContent.setting, tmpContent, priority);

    this.state.data[this.state.currentExtension].settings = settings;

    return this;
  }

  /**
   * This function allows you to change the configuration of a setting.
   */
  setSetting(key: string, content: SettingConfigInput | ((original: SettingConfigInput) => SettingConfigInput)): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();

    if (settings.has(key)) {
      if (content instanceof Function) {
        const original = settings.get(key);
        content = content(original) as SettingConfigInternal;
      }

      settings.setContent(key, content as SettingConfigInternal);
    }

    return this;
  }

  /**
   * This function allows you to change the priority of a setting.
   */
  setSettingPriority(key: string, priority: number): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();

    if (settings.has(key)) {
      settings.setPriority(key, priority);
    }

    return this;
  }

  /**
   * This function allows you to remove a setting.
   */
  removeSetting(key: string): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();

    if (settings.has(key)) {
      settings.remove(key);
    }

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
   */
  registerPermission(content: PermissionConfig, permissionType: PermissionType, priority = 0): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const permissions = this.state.data[this.state.currentExtension].permissions || {};

    const permissionsForType = permissions[permissionType] || new ItemList();

    if (!content.permission && !content.id) {
      throw new Error('Permission definition must have either a permission or id attribute.');
    }

    permissionsForType.add(content.permission || content.id!, content, priority);

    this.state.data[this.state.currentExtension].permissions = { ...permissions, [permissionType]: permissionsForType };

    return this;
  }

  /**
   * This function allows you to change the configuration of a permission.
   */
  setPermission(key: string, content: PermissionConfig | ((original: PermissionConfig) => PermissionConfig), permissionType: PermissionType): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const permissions = this.state.data[this.state.currentExtension].permissions || {};
    const permissionsForType = permissions[permissionType] || new ItemList();

    if (permissionsForType.has(key)) {
      if (content instanceof Function) {
        const original = permissionsForType.get(key);
        content = content(original) as PermissionConfig;
      }

      permissionsForType.setContent(key, content);
    }

    return this;
  }

  /**
   * This function allows you to change the priority of a permission.
   */
  setPermissionPriority(key: string, permissionType: PermissionType, priority: number): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const permissions = this.state.data[this.state.currentExtension].permissions;
    const permissionsForType = permissions?.[permissionType] || new ItemList();

    if (permissionsForType.has(key)) {
      permissionsForType.setPriority(key, priority);
    }

    return this;
  }

  /**
   * This function allows you to remove a permission.
   */
  removePermission(key: string, permissionType: PermissionType): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const permissions = this.state.data[this.state.currentExtension].permissions;
    const permissionsForType = permissions?.[permissionType] || new ItemList();

    if (permissionsForType.has(key)) {
      permissionsForType.remove(key);
    }

    return this;
  }

  /**
   * Replace the default extension page with a custom component.
   * This component would typically extend ExtensionPage
   */
  registerPage(component: CustomExtensionPage): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    this.state.data[this.state.currentExtension].page = component;

    return this;
  }

  /**
   * Get an extension's registered settings
   */
  getSettings(extensionId: string): SettingConfigInternal[] | undefined {
    return this.state.data[extensionId]?.settings?.toArray();
  }

  /**
   * Get an ItemList of all extensions' registered permissions
   */
  getAllPermissions(type: PermissionType): ItemList<PermissionConfig> {
    const items = new ItemList<PermissionConfig>();

    Object.keys(this.state.data).map((extension) => {
      const extPerms = this.state.data[extension]?.permissions?.[type];
      if (this.extensionHasPermissions(extension) && extPerms !== undefined) {
        items.merge(extPerms);
      }
    });

    return items;
  }

  /**
   * Get a singular extension's registered permissions
   */
  getExtensionPermissions(extension: string, type: PermissionType): ItemList<PermissionConfig> {
    const extPerms = this.state.data[extension]?.permissions?.[type];
    if (this.extensionHasPermissions(extension) && extPerms != null) {
      return extPerms;
    }

    return new ItemList();
  }

  /**
   * Checks whether a given extension has registered permissions.
   */
  extensionHasPermissions(extension: string) {
    return this.state.data[extension]?.permissions !== undefined;
  }

  /**
   * Returns an extension's custom page component if it exists.
   */
  getPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs>(extension: string): CustomExtensionPage<Attrs> | undefined {
    return this.state.data[extension]?.page as CustomExtensionPage<Attrs> | undefined;
  }

  getData() {
    return this.state.data;
  }
}
