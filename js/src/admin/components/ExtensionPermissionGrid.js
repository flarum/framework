import PermissionGrid from './PermissionGrid';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';

export default class ExtensionPermissionGrid extends PermissionGrid {
  oninit(vnode) {
    super.oninit(vnode);

    this.extensionId = this.attrs.extensionId;
  }

  permissionItems() {
    const permissionCategories = super.permissionItems();

    permissionCategories.items = Object.entries(permissionCategories.items)
      .filter(([category, info]) => info.content.children.length > 0)
      .reduce((obj, [category, info]) => {
        obj[category] = info;
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

  scopeControlItems() {
    const items = new ItemList();

    items.add(
      'configureScopes',
      <Button className="Button Button--text" onclick={() => m.route.set(app.route('permissions'))}>
        {app.translator.trans('core.admin.extension.configure_scopes')}
      </Button>
    );

    return items;
  }
}
