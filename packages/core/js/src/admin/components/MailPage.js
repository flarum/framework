import Page from './Page';
import FieldSet from '../../common/components/FieldSet';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import Select from '../../common/components/Select';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import saveSettings from '../utils/saveSettings';

// From https://www.30secondsofcode.org/snippet/deepFlatten
// Array.prototype.flatMap is not supported in IE or Edge
const deepFlatten = arr => [].concat(...arr.map(v => (Array.isArray(v) ? deepFlatten(v) : v)));

export default class MailPage extends Page {
  init() {
    super.init();

    this.loading = true;
    this.saving = false;

    this.driverFields = {};
    this.fields = ['mail_driver', 'mail_from'];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach(key => this.values[key] = m.prop(settings[key]));

    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/mail-drivers'
    }).then(response => {
      this.driverFields = response['data'].reduce(
        (hash, driver) => ({...hash, [driver['id']]: driver['attributes']['fields']}),
        {}
      );

      for (const driver in this.driverFields) {
        for (const field in this.driverFields[driver]) {
          this.fields.push(field);
          this.values[field] = m.prop(settings[field]);
        }
      }

      this.loading = false;
      m.redraw();
    });
  }

  view() {
    if (this.loading) {
      return (
        <div className="MailPage">
          <div className="container">
            <LoadingIndicator />
          </div>
        </div>
      );
    }

    const fields = this.driverFields[this.values.mail_driver()];
    const fieldKeys = Object.keys(fields);

    return (
      <div className="MailPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <h2>{app.translator.trans('core.admin.email.heading')}</h2>
            <div className="helpText">
              {app.translator.trans('core.admin.email.text')}
            </div>

            {FieldSet.component({
              label: app.translator.trans('core.admin.email.addresses_heading'),
              className: 'MailPage-MailSettings',
              children: [
                <div className="MailPage-MailSettings-input">
                  <label>{app.translator.trans('core.admin.email.from_label')}</label>
                  <input className="FormControl" value={this.values.mail_from() || ''} oninput={m.withAttr('value', this.values.mail_from)} />
                </div>
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.email.driver_heading'),
              className: 'MailPage-MailSettings',
              children: [
                <div className="MailPage-MailSettings-input">
                  <label>{app.translator.trans('core.admin.email.driver_label')}</label>
                  <Select value={this.values.mail_driver()} options={Object.keys(this.driverFields).reduce((memo, val) => ({...memo, [val]: val}), {})} onchange={this.values.mail_driver} />
                </div>
              ]
            })}

            {fieldKeys.length > 0 && FieldSet.component({
              label: app.translator.trans(`core.admin.email.${this.values.mail_driver()}_heading`),
              className: 'MailPage-MailSettings',
              children: [
                fieldKeys.filter(field => fields[field] && fields[field].indexOf('required') !== -1 && !this.values[field]()).length > 0 && Alert.component({
                  children: app.translator.trans('core.admin.email.incomplete_configuration_text'),
                  dismissible: false,
                }),

                <div className="MailPage-MailSettings-input">
                  {fieldKeys.map(field => [
                    <label>{app.translator.trans(`core.admin.email.${field}_label`)} {(fields[field] || '').indexOf('required') !== -1 ? '*' : ''}</label>,
                    this.renderField(field),
                  ])}
                </div>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.email.submit_button'),
              loading: this.saving,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    );
  }

  renderField(name) {
    const driver = this.values.mail_driver();
    const field = this.driverFields[driver][name];
    const prop = this.values[name];

    if (typeof field === 'string') {
      return <input className="FormControl" value={prop() || ''} oninput={m.withAttr('value', prop)} required={(fields[field] || '').indexOf('required') !== -1} />;
    } else {
      return <Select value={prop()} options={field} onchange={prop} />;
    }
  }

  changed() {
    return this.fields.some(key => this.values[key]() !== app.data.settings[key]);
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.saving) return;

    this.saving = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach(key => settings[key] = this.values[key]());

    saveSettings(settings)
      .then(() => {
        app.alerts.show(this.successAlert = new Alert({type: 'success', children: app.translator.trans('core.admin.basics.saved_message')}));
      })
      .catch(() => {})
      .then(() => {
        this.saving = false;
        m.redraw();
      });
  }
}
