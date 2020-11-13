import PermissionGrid from './PermissionGrid';
import ItemList from "../../common/utils/ItemList";

export default class ExtensionPermissionGrid extends PermissionGrid {
  oninit(vnode) {
    super.oninit(vnode);

    this.extensionId = this.attrs.extensionId;
  }

  permissionItems() {
    const permissionCategories = super.permissionItems();

    Object.keys(permissionCategories.items).map((item) => {
      if (permissionCategories.items[item].content.children.length === 0) {
        permissionCategories.remove([item]);
      }
    });

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
