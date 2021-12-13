import app from '../app';
import SelectDropdown from '../../common/components/SelectDropdown';
import Button from '../../common/components/Button';
import saveSettings from '../utils/saveSettings';

export default class SettingDropdown extends SelectDropdown {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = 'SettingDropdown';
    attrs.buttonClassName = 'Button Button--text';
    attrs.caretIcon = 'fas fa-caret-down';
    attrs.defaultLabel = 'Custom';

    if ('key' in attrs) {
      attrs.setting = attrs.key;
      delete attrs.key;
    }
  }

  view(vnode) {
    return super.view({
      ...vnode,
      children: this.attrs.options.map(({ value, label }) => {
        const active = app.data.settings[this.attrs.setting] === value;

        return Button.component(
          {
            icon: active ? 'fas fa-check' : true,
            onclick: saveSettings.bind(this, { [this.attrs.setting]: value }),
            active,
          },
          label
        );
      }),
    });
  }
}
