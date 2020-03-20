import Page from "./Page";
import Button from "../../common/components/Button";
import saveSettings from "../utils/saveSettings";
import Alert from "../../common/components/Alert";
import FieldSet from "../../common/components/FieldSet";

export default class AdvancedPage extends Page {
  init() {
    super.init();

    this.loading = false;
    this.fields = [
      'post_flood_interval',
    ];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach(key => this.values[key] = m.prop(settings[key]));

  }

  view() {
    return (
      <div className="AdvancedPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <h2>{app.translator.trans('core.admin.advanced.heading_security')}</h2>
            <div className="helpText">
              {app.translator.trans('core.admin.advanced.help_text')}
            </div>
            <br/>

            {FieldSet.component({
              label: app.translator.trans('core.admin.advanced.post_flood_interval'),
              children: [
                <div className="helpText">
                  {app.translator.trans('core.admin.advanced.post_flood_interval_text')}
                </div>,
                <input type="number" className="FormControl" value={this.values.post_flood_interval()} oninput={m.withAttr('value', this.values.post_flood_interval)}/>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.basics.submit_button'),
              loading: this.loading,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    )
  }

  changed() {
    return this.fields.some(key => this.values[key]() !== app.data.settings[key]);
  }

  onsubmit(e) {
    e.preventDefault();

    const positiveInteger = /^\+?(0|[1-9]\d*)$/;
    if(!positiveInteger.test(this.values.post_flood_interval()) || this.values.post_flood_interval().length === 0) {
      alert(app.translator.trans('core.admin.advanced.enter_positive_number'));
      return;
    }

    if (this.loading) return;

    this.loading = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};
    this.fields.forEach(key => settings[key] = this.values[key]());

    saveSettings(settings)
      .then(() => {
        app.alerts.show(this.successAlert = new Alert({type: 'success', children: app.translator.trans('core.admin.basics.saved_message')}));
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
