import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import { SettingsComponentOptions } from '../components/AdminPage';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';
import { PermissionConfig, PermissionType } from '../components/PermissionGrid';

type SettingConfigInput = SettingsComponentOptions | (() => Mithril.Children);

type SettingConfigInternal = SettingsComponentOptions | ((() => Mithril.Children) & { setting: string });

export type CustomExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> = new () => ExtensionPage<Attrs>;

type ExtensionConfig = {
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

export default class ExtensionData {
  protected state: InnerDataActiveExtension | InnerDataNoActiveExtension = {
    currentExtension: null,
    data: {},
  };

  /**
   * This function simply takes the extension id
   *
   * @example
   * app.extensionData.for('flarum-tags')
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
  registerSetting(content: SettingConfigInput, priority = 0): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const tmpContent = content as SettingConfigInternal;

    // Callbacks can be passed in instead of settings to display custom content.
    // By default, they will be added with the `null` key, since they don't have a `.setting` attr.
    // To support multiple such items for one extension, we assign a random ID.
    // 36 is arbitrary length, but makes collisions very unlikely.
    if (tmpContent instanceof Function) {
      tmpContent.setting = Math.random().toString(36);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();
    settings.add(tmpContent.setting, tmpContent, priority);

    this.state.data[this.state.currentExtension].settings = settings;

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

    permissionsForType.add(content.permission, content, priority);

    this.state.data[this.state.currentExtension].permissions = { ...permissions, [permissionType]: permissionsForType };

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
  getAllExtensionPermissions(type: PermissionType): ItemList<PermissionConfig> {
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
}
