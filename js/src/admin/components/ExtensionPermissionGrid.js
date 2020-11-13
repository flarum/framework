import PermissionGrid from './PermissionGrid';
import ItemList from '../../common/utils/ItemList';

export default class ExtensionPermissionGrid extends PermissionGrid {
  oninit(vnode) {
    super.oninit(vnode);

    this.extensionId = this.attrs.extensionId;
  }

  permissionItems() {
    const permissionCategories = super.permissionItems();

    permissionCategories.items = Object.entries(permissionCategories.items)
      .filter(([k, v]) => v.content.children.length > 0)
      .reduce((obj, [key, v]) => {
        obj[key] = v;
        return obj;
      }, {});

    return permissionCategories;
  }

  viewItems() {
    return app.extensionData.getExtensionPermissions(this.extensionId, 'view') || new ItemList();
  }

  startItems() {
    return app.extensionData.getExtensionPermissions(this.extensionId, 'start') || new ItemList();
  }

  replyItems() {
    return app.extensionData.getExtensionPermissions(this.extensionId, 'reply') || new ItemList();
  }

  moderateItems() {
    return app.extensionData.getExtensionPermissions(this.extensionId, 'moderate') || new ItemList();
  }
}
