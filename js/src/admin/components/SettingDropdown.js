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
  }

  view(vnode) {
    return super.view({
      ...vnode,
      children: this.attrs.options.map(({ value, label }) => {
        const active = app.data.settings[this.attrs.key] === value;

        return Button.component(
          {
            icon: active ? 'fas fa-check' : true,
            onclick: saveSettings.bind(this, { [this.attrs.key]: value }),
            active,
          },
          label
        );
      }),
    });
  }
}
