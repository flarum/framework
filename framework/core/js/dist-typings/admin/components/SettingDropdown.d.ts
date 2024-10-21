import SelectDropdown, { ISelectDropdownAttrs } from '../../common/components/SelectDropdown';
import Mithril from 'mithril';
export type SettingDropdownOption = {
    value: any;
    label: string;
};
export interface ISettingDropdownAttrs extends ISelectDropdownAttrs {
    setting?: string;
    options: Array<SettingDropdownOption>;
}
export default class SettingDropdown<CustomAttrs extends ISettingDropdownAttrs = ISettingDropdownAttrs> extends SelectDropdown<CustomAttrs> {
    static initAttrs(attrs: ISettingDropdownAttrs): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
