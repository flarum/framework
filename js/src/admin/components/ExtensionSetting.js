import Component from '../../common/Component';
import classList from "../../common/utils/classList";
import Stream from "../../common/utils/Stream";
import withAttr from "../../common/utils/withAttr";

export default class ExtensionSetting extends Component {
  view(vnode) {
    const type = this.attrs.type;
    this.setting = this.attrs.setting;

    return (
      <div className="Form-group">
        {type === 'checkbox' ?
          <label className={classList([type, this.attrs.className])}>
            {this.attrs.label}
            <input type={type} checked={app.data.settings[this.setting]} onchange={withAttr('checked', this.updateSetting.bind(this))}/>
          </label>
          : (
            <label>{this.attrs.label}</label>,
              <input className="FormControl" value={app.data.settings[this.setting]} oninput={withAttr('value', this.updateSetting.bind(this))}/>
          )
        }
      </div>
    )
  }

  updateSetting(key) {
    app.pendingSettings[this.setting] = Stream(key);
  }
}
