import Page from './Page';
import FieldSet from '../../common/components/FieldSet';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import Select from '../../common/components/Select';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import saveSettings from '../utils/saveSettings';

export default class MailPage extends Page {
  init() {
    super.init();

    this.saving = false;
    this.refresh();
  }

  refresh() {
    this.loading = true;

    this.driverFields = {};
    this.fields = ['mail_driver', 'mail_from'];
    this.values = {};
    this.status = { sending: false, errors: {} };

    const settings = app.data.settings;
    this.fields.forEach((key) => (this.values[key] = m.prop(settings[key])));

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/mail-settings',
      })
      .then((response) => {
        this.driverFields = response['data']['attributes']['fields'];
        this.status.sending = response['data']['attributes']['sending'];
        this.status.errors = response['data']['attributes']['errors'];

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
    if (this.loading || this.saving) {
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
            <div className="helpText">{app.translator.trans('core.admin.email.text')}</div>

            {FieldSet.component({
              label: app.translator.trans('core.admin.email.addresses_heading'),
              className: 'MailPage-MailSettings',
              children: [
                <div className="MailPage-MailSettings-input">
                  <label>
                    {app.translator.trans('core.admin.email.from_label')}
                    <input className="FormControl" value={this.values.mail_from() || ''} oninput={m.withAttr('value', this.values.mail_from)} />
                  </label>
                </div>,
              ],
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.email.driver_heading'),
              className: 'MailPage-MailSettings',
              children: [
                <div className="MailPage-MailSettings-input">
                  <label>
                    {app.translator.trans('core.admin.email.driver_label')}
                    <Select
                      value={this.values.mail_driver()}
                      options={Object.keys(this.driverFields).reduce((memo, val) => ({ ...memo, [val]: val }), {})}
                      onchange={this.values.mail_driver}
                    />
                  </label>
                </div>,
              ],
            })}

            {this.status.sending ||
              Alert.component({
                children: app.translator.trans('core.admin.email.not_sending_message'),
                dismissible: false,
              })}

            {fieldKeys.length > 0 &&
              FieldSet.component({
                label: app.translator.trans(`core.admin.email.${this.values.mail_driver()}_heading`),
                className: 'MailPage-MailSettings',
                children: [
                  <div className="MailPage-MailSettings-input">
                    {fieldKeys.map((field) => [
                      <label>
                        {app.translator.trans(`core.admin.email.${field}_label`)}
                        {this.renderField(field)}
                      </label>,
                      this.status.errors[field] && <p className="ValidationError">{this.status.errors[field]}</p>,
                    ])}
                  </div>,
                ],
              })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.email.submit_button'),
              disabled: !this.changed(),
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
      return <input className="FormControl" value={prop() || ''} oninput={m.withAttr('value', prop)} />;
    } else {
      return <Select value={prop()} options={field} onchange={prop} />;
    }
  }

  changed() {
    return this.fields.some((key) => this.values[key]() !== app.data.settings[key]);
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.saving) return;

    this.saving = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach((key) => (settings[key] = this.values[key]()));

    saveSettings(settings)
      .then(() => {
        app.alerts.show((this.successAlert = new Alert({ type: 'success', children: app.translator.trans('core.admin.basics.saved_message') })));
      })
      .catch(() => {})
      .then(() => {
        this.saving = false;
        this.refresh();
      });
  }
}
