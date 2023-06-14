import app from '../../admin/app';
import FieldSet from '../../common/components/FieldSet';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type { AlertIdentifier } from '../../common/states/AlertManagerState';
import type Mithril from 'mithril';
import type { SaveSubmitEvent } from './AdminPage';

export interface MailSettings {
  data: {
    attributes: {
      fields: Record<string, any>;
      sending: boolean;
      errors: any[];
    };
  };
}

export default class MailPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  sendingTest = false;
  status?: { sending: boolean; errors: any };
  driverFields?: Record<string, any>;
  testEmailSuccessAlert?: AlertIdentifier;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

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
      .request<MailSettings>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/mail/settings',
      })
      .then((response) => {
        this.driverFields = response.data.attributes.fields;
        this.status!.sending = response.data.attributes.sending;
        this.status!.errors = response.data.attributes.errors;

        this.loading = false;
        m.redraw();
      });
  }

  content() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const fields = this.driverFields![this.setting('mail_driver')()];
    const fieldKeys = Object.keys(fields);

    return (
      <div className="Form">
        {this.buildSettingComponent({
          type: 'text',
          setting: 'mail_from',
          label: app.translator.trans('core.admin.email.addresses_heading'),
        })}
        {this.buildSettingComponent({
          type: 'select',
          setting: 'mail_driver',
          options: Object.keys(this.driverFields!).reduce((memo, val) => ({ ...memo, [val]: val }), {}),
          label: app.translator.trans('core.admin.email.driver_heading'),
        })}
        {this.status!.sending || <Alert dismissible={false}>{app.translator.trans('core.admin.email.not_sending_message')}</Alert>}

        {!!fieldKeys.length && (
          <FieldSet label={app.translator.trans(`core.admin.email.${this.setting('mail_driver')()}_heading`)} className="MailPage-MailSettings">
            <div className="MailPage-MailSettings-input">
              {fieldKeys.map((field) => {
                const fieldInfo = fields[field];

                return (
                  <>
                    {this.buildSettingComponent({
                      type: typeof fieldInfo === 'string' ? 'text' : 'select',
                      label: app.translator.trans(`core.admin.email.${field}_label`),
                      setting: field,
                      options: fieldInfo,
                    })}
                    {this.status!.errors[field] && <p className="ValidationError">{this.status!.errors[field]}</p>}
                  </>
                );
              })}
            </div>
          </FieldSet>
        )}
        {this.submitButton()}

        <FieldSet label={app.translator.trans('core.admin.email.send_test_mail_heading')} className="MailPage-MailSettings">
          <div className="helpText">{app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user!.email() })}</div>
          <Button className="Button Button--primary" disabled={this.sendingTest || this.isChanged()} onclick={() => this.sendTestEmail()}>
            {app.translator.trans('core.admin.email.send_test_mail_button')}
          </Button>
        </FieldSet>
      </div>
    );
  }

  sendTestEmail() {
    if (this.sendingTest) return;

    this.sendingTest = true;

    if (this.testEmailSuccessAlert) app.alerts.dismiss(this.testEmailSuccessAlert);

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

  saveSettings(e: SaveSubmitEvent) {
    return super.saveSettings(e).then(() => this.refresh());
  }
}
