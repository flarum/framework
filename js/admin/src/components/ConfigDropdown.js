import SelectDropdown from 'flarum/components/SelectDropdown';
import Button from 'flarum/components/Button';
import saveConfig from 'flarum/utils/saveConfig';

export default class ConfigDropdown extends SelectDropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'ConfigDropdown';
    props.buttonClassName = 'Button Button--text';
    props.caretIcon = 'caret-down';
    props.defaultLabel = 'Custom';

    props.children = props.options.map(({value, label}) => {
      const active = app.config[props.key] === value;

      return Button.component({
        children: label,
        icon: active ? 'check' : true,
        onclick: saveConfig.bind(this, {[props.key]: value}),
        active
      });
    });
  }
}
