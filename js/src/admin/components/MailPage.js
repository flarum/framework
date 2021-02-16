import FieldSet from '../../common/components/FieldSet';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import AdminPage from './AdminPage';

export default class MailPage extends AdminPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.sendingTest = false;
    this.refresh();
  }

  headerInfo() {
    return {
      className: 'MailPage',
      icon: 'fas fa-envelope',
      title: app.translator.trans('core.admin.email.title'),
      description: app.translator.trans('core.admin.email.description'),
    };
  }

  refresh() {
    this.loading = true;

    this.status = { sending: false, errors: {} };

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/mail/settings',
      })
      .then((response) => {
        this.driverFields = response['data']['attributes']['fields'];
        this.status.sending = response['data']['attributes']['sending'];
        this.status.errors = response['data']['attributes']['errors'];

        this.loading = false;
        m.redraw();
      });
  }

  content() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const fields = this.driverFields[this.setting('mail_driver')()];
    const fieldKeys = Object.keys(fields);

    return (
      <div className="Form">
        {this.buildSettingComponent({
          type: 'text',
          setting: 'mail_from',
          label: app.translator.trans('core.admin.email.addresses_heading'),
          className: 'MailPage-MailSettings',
        })}
        {this.buildSettingComponent({
          type: 'select',
          setting: 'mail_driver',
          options: Object.keys(this.driverFields).reduce((memo, val) => ({ ...memo, [val]: val }), {}),
          label: app.translator.trans('core.admin.email.driver_heading'),
          className: 'MailPage-MailSettings',
        })}
        {this.status.sending ||
          Alert.component(
            {
              dismissible: false,
            },
            app.translator.trans('core.admin.email.not_sending_message')
          )}

        {fieldKeys.length > 0 && (
          <FieldSet label={app.translator.trans(`core.admin.email.${this.setting('mail_driver')()}_heading`)} className="MailPage-MailSettings">
            <div className="MailPage-MailSettings-input">
              {fieldKeys.map((field) => {
                const fieldInfo = fields[field];

                return [
                  this.buildSettingComponent({
                    type: typeof this.setting(field)() === 'string' ? 'text' : 'select',
                    label: app.translator.trans(`core.admin.email.${field}_label`),
                    setting: field,
                    options: fieldInfo,
                  }),
                  this.status.errors[field] && <p className="ValidationError">{this.status.errors[field]}</p>,
                ];
              })}
            </div>
          </FieldSet>
        )}
        {this.submitButton()}

        <FieldSet label={app.translator.trans('core.admin.email.send_test_mail_heading')} className="MailPage-MailSettings">
          <div className="helpText">{app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user.email() })}</div>
          {Button.component(
            {
              className: 'Button Button--primary',
              disabled: this.sendingTest || this.isChanged(),
              onclick: () => this.sendTestEmail(),
            },
            app.translator.trans('core.admin.email.send_test_mail_button')
          )}
        </FieldSet>
      </div>
    );
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

  saveSettings(e) {
    super.saveSettings(e).then(this.refresh());
  }
}
