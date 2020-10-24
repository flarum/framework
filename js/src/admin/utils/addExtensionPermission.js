import ItemList from '../../common/utils/ItemList';

export default class addExtensionPermission {
  constructor(extension) {
    this.extension = extension;
    app.extensionPermissions[extension] = {};
  }

  add(permission, icon, label, type, priority = 0) {
    if (app.extensionPermissions[this.extension][type] === undefined) {
      app.extensionPermissions[this.extension][type] = new ItemList();
    }

    app.extensionPermissions[this.extension][type].add(
      permission,
      {
        icon,
        label,
        permission,
      },
      priority
    );

    return this;
  }
}
