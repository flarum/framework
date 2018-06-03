import SelectDropdown from 'components/SelectDropdown';
import Button from 'components/Button';
import saveSettings from 'utils/saveSettings';

export default class SettingDropdown extends SelectDropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'SettingDropdown';
    props.buttonClassName = 'Button Button--text';
    props.caretIcon = 'fa fa-caret-down';
    props.defaultLabel = 'Custom';

    props.children = props.options.map(({value, label}) => {
      const active = app.data.settings[props.key] === value;

      return Button.component({
        children: label,
        icon: active ? 'fa fa-check' : true,
        onclick: saveSettings.bind(this, {[props.key]: value}),
        active
      });
    });
  }
}
