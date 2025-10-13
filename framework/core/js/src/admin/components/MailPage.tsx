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
import Form from '../../common/components/Form';
import ItemList from '../../common/utils/ItemList';

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

    const mailSettings = this.mailSettingItems().toArray();

    return (
      <>
        <Form>
          {mailSettings.map((settingComponent) => settingComponent)}
          <div className="Form-group Form-controls">{this.submitButton()}</div>
        </Form>
        <Form>
          <FieldSet
            label={app.translator.trans('core.admin.email.send_test_mail_heading')}
            className="FieldSet--col MailPage-MailSettings"
            description={app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user!.email() })}
          >
            <Button className="Button Button--primary" disabled={this.sendingTest || this.isChanged()} onclick={() => this.sendTestEmail()}>
              {app.translator.trans('core.admin.email.send_test_mail_button')}
            </Button>
          </FieldSet>
        </Form>
      </>
    );
  }

  mailSettingItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const fields = this.driverFields![this.setting('mail_driver')()] || {};
    const fieldKeys = Object.keys(fields);

    if (!this.status!.sending) {
      items.add('status', <Alert dismissible={false}>{app.translator.trans('core.admin.email.not_sending_message')}</Alert>);
    }

    items.add(
      'mail_from',
      this.buildSettingComponent({
        type: 'text',
        setting: 'mail_from',
        label: app.translator.trans('core.admin.email.addresses_heading'),
      }),
      80
    );

    items.add(
      'mail_format',
      this.buildSettingComponent({
        type: 'select',
        setting: 'mail_format',
        options: {
          multipart: app.translator.trans('core.admin.email.format.multipart_option'),
          plain: app.translator.trans('core.admin.email.format.plain_option'),
          html: app.translator.trans('core.admin.email.format.html_option'),
        },
        label: app.translator.trans('core.admin.email.format_heading'),
        help: app.translator.trans('core.admin.email.format_help'),
      }),
      70
    );

    items.add(
      'mail_driver',
      this.buildSettingComponent({
        type: 'select',
        setting: 'mail_driver',
        options: Object.keys(this.driverFields!).reduce((memo, val) => ({ ...memo, [val]: val }), {}),
        label: app.translator.trans('core.admin.email.driver_heading'),
      }),
      60
    );

    if (!!fieldKeys.length) {
      items.add(
        'mail_driver_settings',
        <FieldSet
          label={app.translator.trans(`core.admin.email.${this.setting('mail_driver')()}_heading`)}
          className="MailPage-MailSettings FieldSet--form"
        >
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
        </FieldSet>,
        50
      );
    }

    return items;
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

  static register() {
    app.generalIndex.group('core-mail', {
      label: app.translator.trans('core.admin.email.title', {}, true),
      icon: {
        name: 'fas fa-envelope',
      },
      link: app.route('mail'),
    });

    app.generalIndex.for('core-mail').add('settings', [
      {
        id: 'mail_from',
        label: app.translator.trans('core.admin.email.addresses_heading', {}, true),
      },
      {
        id: 'mail_format',
        label: app.translator.trans('core.admin.email.format_heading', {}, true),
        help: app.translator.trans('core.admin.email.format_help', {}, true),
      },
      {
        id: 'mail_driver',
        label: app.translator.trans('core.admin.email.driver_heading', {}, true),
      },
      {
        id: 'send_test_mail_heading',
        label: app.translator.trans('core.admin.email.send_test_mail_heading', {}, true),
        help: app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user!.email() }, true),
      },
    ]);
  }
}
