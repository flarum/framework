import Component, { ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface PermissionConfig {
    permission: string;
    icon: string;
    label: Mithril.Children;
    allowGuest?: boolean;
}
export interface PermissionSetting {
    setting: () => Mithril.Children;
    icon: string;
    label: Mithril.Children;
}
export type PermissionGridEntry = PermissionConfig | PermissionSetting;
export type PermissionType = 'view' | 'start' | 'reply' | 'moderate';
export interface ScopeItem {
    label: Mithril.Children;
    render: (permission: PermissionGridEntry) => Mithril.Children;
    onremove?: () => void;
}
export interface IPermissionGridAttrs extends ComponentAttrs {
}
export default class PermissionGrid<CustomAttrs extends IPermissionGridAttrs = IPermissionGridAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    permissionItems(): ItemList<{
        label: Mithril.Children;
        children: PermissionGridEntry[];
    }>;
    viewItems(): ItemList<PermissionGridEntry>;
    startItems(): ItemList<PermissionGridEntry>;
    replyItems(): ItemList<PermissionGridEntry>;
    moderateItems(): ItemList<PermissionGridEntry>;
    scopeItems(): ItemList<ScopeItem>;
    scopeControlItems(): ItemList<unknown>;
}
