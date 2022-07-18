import PermissionGrid, { PermissionGridEntry } from './PermissionGrid';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';
export interface IExtensionPermissionGridAttrs {
    extensionId: string;
}
export default class ExtensionPermissionGrid<CustomAttrs extends IExtensionPermissionGridAttrs = IExtensionPermissionGridAttrs> extends PermissionGrid<CustomAttrs> {
    protected extensionId: string;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    permissionItems(): ItemList<{
        label: Mithril.Children;
        children: PermissionGridEntry[];
    }>;
    viewItems(): ItemList<import("flarum/admin/components/PermissionGrid").PermissionConfig>;
    startItems(): ItemList<import("flarum/admin/components/PermissionGrid").PermissionConfig>;
    replyItems(): ItemList<import("flarum/admin/components/PermissionGrid").PermissionConfig>;
    moderateItems(): ItemList<import("flarum/admin/components/PermissionGrid").PermissionConfig>;
    scopeControlItems(): ItemList<unknown>;
}
