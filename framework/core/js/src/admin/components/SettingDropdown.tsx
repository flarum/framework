import app from '../app';
import SelectDropdown, { ISelectDropdownAttrs } from '../../common/components/SelectDropdown';
import Button from '../../common/components/Button';
import saveSettings from '../utils/saveSettings';
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
  static initAttrs(attrs: ISettingDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = 'SettingDropdown';
    attrs.buttonClassName = 'Button Button--text';
    attrs.caretIcon = 'fas fa-caret-down';
    attrs.defaultLabel = 'Custom';

    if ('key' in attrs) {
      attrs.setting = attrs.key?.toString();
      delete attrs.key;
    }
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return super.view({
      ...vnode,
      children: this.attrs.options.map(({ value, label }) => {
        const active = app.data.settings[this.attrs.setting!] === value;

        return (
          <Button icon={active ? 'fas fa-check' : true} onclick={saveSettings.bind(this, { [this.attrs.setting!]: value })} active={active}>
            {label}
          </Button>
        );
      }),
    });
  }
}
