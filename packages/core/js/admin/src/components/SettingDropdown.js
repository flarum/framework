import SelectDropdown from 'flarum/components/SelectDropdown';
import Button from 'flarum/components/Button';
import saveSettings from 'flarum/utils/saveSettings';

export default class SettingDropdown extends SelectDropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'SettingDropdown';
    props.buttonClassName = 'Button Button--text';
    props.caretIcon = 'fas fa-caret-down';
    props.defaultLabel = 'Custom';

    props.children = props.options.map(({value, label}) => {
      const active = app.data.settings[props.key] === value;

      return Button.component({
        children: label,
        icon: active ? 'fas fa-check' : true,
        onclick: saveSettings.bind(this, {[props.key]: value}),
        active
      });
    });
  }
}
