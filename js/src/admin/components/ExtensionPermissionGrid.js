import PermissionGrid from './PermissionGrid';
import ItemList from '../../common/utils/ItemList';

export default class ExtensionPermissionGrid extends PermissionGrid {
  permissionItems() {
    const items = super.permissionItems();

    Object.keys(items.items).map((item) => {
      if (items.items[item].content.children.length === 0) {
        items.remove([item]);
      }
    });

    return items;
  }

  viewItems() {
    return this.getExtensionPermissions('view');
  }

  startItems() {
    return this.getExtensionPermissions('start');
  }

  replyItems() {
    return this.getExtensionPermissions('reply');
  }

  moderateItems() {
    return this.getExtensionPermissions('moderate');
  }

  getExtensionPermissions(type) {
    const items = new ItemList();

    const extensionId = this.attrs.extensionId;

    if (app.extensionData[extensionId] && app.extensionData[extensionId].permissions && app.extensionData[extensionId].permissions[type]) {
      items.merge(app.extensionData[extensionId].permissions[type]);
    }

    return items;
  }
}
