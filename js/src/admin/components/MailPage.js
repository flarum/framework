import Page from '../../common/components/Page';
import FieldSet from '../../common/components/FieldSet';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import Select from '../../common/components/Select';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import saveSettings from '../utils/saveSettings';
import Stream from '../../common/utils/Stream';
import icon from '../../common/helpers/icon';
import AdminHeader from './AdminHeader';

export default class MailPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.saving = false;
    this.sendingTest = false;
    this.refresh();
  }

  refresh() {
    this.loading = true;

    this.driverFields = {};
    this.fields = ['mail_driver', 'mail_from'];
    this.values = {};
    this.status = { sending: false, errors: {} };

    const settings = app.data.settings;
    this.fields.forEach((key) => (this.values[key] = Stream(settings[key])));

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/mail/settings',
      })
      .then((response) => {
        this.driverFields = response['data']['attributes']['fields'];
        this.status.sending = response['data']['attributes']['sending'];
        this.status.errors = response['data']['attributes']['errors'];

        for (const driver in this.driverFields) {
          for (const field in this.driverFields[driver]) {
            this.fields.push(field);
            this.values[field] = Stream(settings[field]);
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
        <AdminHeader icon="fas fa-envelope" description={app.translator.trans('core.admin.email.description')} className="MailPage-header">
          {app.translator.trans('core.admin.email.title')}
        </AdminHeader>
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.email.addresses_heading'),
                className: 'MailPage-MailSettings',
              },
              [
                <div className="MailPage-MailSettings-input">
                  <label>
                    {app.translator.trans('core.admin.email.from_label')}
                    <input className="FormControl" bidi={this.values.mail_from} />
                  </label>
                </div>,
              ]
            )}

            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.email.driver_heading'),
                className: 'MailPage-MailSettings',
              },
              [
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
              ]
            )}

            {this.status.sending ||
              Alert.component(
                {
                  dismissible: false,
                },
                app.translator.trans('core.admin.email.not_sending_message')
              )}

            {fieldKeys.length > 0 &&
              FieldSet.component(
                {
                  label: app.translator.trans(`core.admin.email.${this.values.mail_driver()}_heading`),
                  className: 'MailPage-MailSettings',
                },
                [
                  <div className="MailPage-MailSettings-input">
                    {fieldKeys.map((field) => [
                      <label>
                        {app.translator.trans(`core.admin.email.${field}_label`)}
                        {this.renderField(field)}
                      </label>,
                      this.status.errors[field] && <p className="ValidationError">{this.status.errors[field]}</p>,
                    ])}
                  </div>,
                ]
              )}

            <FieldSet>
              {Button.component(
                {
                  type: 'submit',
                  className: 'Button Button--primary',
                  disabled: !this.changed(),
                },
                app.translator.trans('core.admin.email.submit_button')
              )}
            </FieldSet>

            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.email.send_test_mail_heading'),
                className: 'MailPage-MailSettings',
              },
              [
                <div className="helpText">{app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user.email() })}</div>,
                Button.component(
                  {
                    className: 'Button Button--primary',
                    disabled: this.sendingTest || this.changed(),
                    onclick: () => this.sendTestEmail(),
                  },
                  app.translator.trans('core.admin.email.send_test_mail_button')
                ),
              ]
            )}
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
      return <input className="FormControl" bidi={prop} />;
    } else {
      return <Select value={prop()} options={field} onchange={prop} />;
    }
  }

  changed() {
    return this.fields.some((key) => this.values[key]() !== app.data.settings[key]);
  }

  sendTestEmail() {
    if (this.saving || this.sendingTest) return;

    this.sendingTest = true;
    app.alerts.dismiss(this.testEmailSuccessAlert);

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/mail/test',
      })
      .then((response) => {
        this.sendingTest = false;
        this.testEmailSuccessAlert = app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.email.send_test_mail_success'));
      })
      .catch((error) => {
        this.sendingTest = false;
        m.redraw();
        throw error;
      });
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.saving || this.sendingTest) return;

    this.saving = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach((key) => (settings[key] = this.values[key]()));

    saveSettings(settings)
      .then(() => {
        this.successAlert = app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.basics.saved_message'));
      })
      .catch(() => {})
      .then(() => {
        this.saving = false;
        this.refresh();
      });
  }
}
