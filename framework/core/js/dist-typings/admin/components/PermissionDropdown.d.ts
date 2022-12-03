import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import Mithril from 'mithril';
export interface IPermissionDropdownAttrs extends IDropdownAttrs {
    permission: string;
}
export default class PermissionDropdown<CustomAttrs extends IPermissionDropdownAttrs = IPermissionDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: IPermissionDropdownAttrs): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    save(groupIds: string[]): void;
    toggle(groupId: string): void;
    isGroupDisabled(id: string): boolean;
}
