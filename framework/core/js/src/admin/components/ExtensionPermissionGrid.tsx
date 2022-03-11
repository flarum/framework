import app from '../../admin/app';
import PermissionGrid, { PermissionGridEntry } from './PermissionGrid';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';

export interface IExtensionPermissionGridAttrs {
  extensionId: string;
}

export default class ExtensionPermissionGrid<
  CustomAttrs extends IExtensionPermissionGridAttrs = IExtensionPermissionGridAttrs
> extends PermissionGrid<CustomAttrs> {
  protected extensionId!: string;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.extensionId = this.attrs.extensionId;
  }

  permissionItems() {
    const items = new ItemList<{ label: Mithril.Children; children: PermissionGridEntry[] }>();

    super
      .permissionItems()
      .toArray()
      .filter((item) => item.children.length > 0)
      .forEach((item) => {
        items.add(item.itemName, item);
      });

    return items;
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
