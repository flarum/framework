import app from '../../admin/app';
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
import SettingsSection from './SettingsSection';

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

    const driver = this.setting('mail_driver')();

    return (
      <>
        <Form className="AdminPage-settingsForm--sectioned MailPage-form">
          {this.itemSections(this.mailSettingItems(), [
            {
              id: 'mail-sending',
              icon: 'fas fa-paper-plane',
              label: app.translator.trans('core.admin.email.sections.sending.label'),
              description: app.translator.trans('core.admin.email.sections.sending.description'),
              items: ['mail_from', 'mail_format', 'mail_driver'],
            },
            {
              id: 'mail-driver',
              icon: 'fas fa-server',
              label: app.translator.trans(`core.admin.email.${driver}_heading`),
              description: this.driverDescription(driver),
              items: ['mail_driver_settings'],
            },
          ]).toArray()}
          <SettingsSection
            id="mail-test"
            icon="fas fa-vial"
            label={app.translator.trans('core.admin.email.send_test_mail_heading')}
            description={app.translator.trans('core.admin.email.send_test_mail_text', { email: app.session.user!.email() })}
          >
            <div className="Form-group">
              <Button className="Button" disabled={this.sendingTest || this.isChanged()} onclick={() => this.sendTestEmail()}>
                {app.translator.trans('core.admin.email.send_test_mail_button')}
              </Button>
            </div>
          </SettingsSection>
          <div className="Form-group Form-controls">{this.submitButton()}</div>
        </Form>
      </>
    );
  }

  /**
   * The description of a driver's settings, if it has one.
   */
  driverDescription(driver: string): Mithril.Children {
    const key = `core.admin.email.${driver}_description`;
    const text = app.translator.trans(key);

    return text === key ? null : text;
  }

  mailSettingItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const fields = this.driverFields![this.setting('mail_driver')()] || {};
    const fieldKeys = Object.keys(fields);

    if (!this.status!.sending) {
      items.add('status', <Alert dismissible={false}>{app.translator.trans('core.admin.email.not_sending_message')}</Alert>, 100);
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
        <div className="MailPage-MailSettings">
          {fieldKeys.map((field) => {
            const fieldInfo = fields[field];

            // For encryption field, show all help text options above the dropdown
            let helpText;
            if (field === 'mail_encryption') {
              const tlsHelp = app.translator.trans('core.admin.email.mail_encryption_tls_help');
              const sslHelp = app.translator.trans('core.admin.email.mail_encryption_ssl_help');
              const noneHelp = app.translator.trans('core.admin.email.mail_encryption_none_help');

              helpText = (
                <>
                  <div>
                    <strong>TLS:</strong> {tlsHelp}
                  </div>
                  <div>
                    <strong>SSL:</strong> {sslHelp}
                  </div>
                  <div>
                    <strong>None:</strong> {noneHelp}
                  </div>
                </>
              );
            } else {
              const helpKey = `core.admin.email.${field}_help`;
              const translatedHelp = app.translator.trans(helpKey);
              helpText = translatedHelp !== helpKey ? translatedHelp : undefined;
            }

            return (
              <>
                {this.buildSettingComponent({
                  type: typeof fieldInfo === 'string' ? 'text' : typeof fieldInfo === 'boolean' ? 'bool' : 'select',
                  label: app.translator.trans(`core.admin.email.${field}_label`),
                  setting: field,
                  options: fieldInfo,
                  help: helpText,
                })}
                {this.status!.errors[field] && (
                  <div className="ValidationError">
                    {Array.isArray(this.status!.errors[field]) ? (
                      this.status!.errors[field].map((error: string) => <p key={error}>{error}</p>)
                    ) : (
                      <p>{this.status!.errors[field]}</p>
                    )}
                  </div>
                )}
              </>
            );
          })}
        </div>,
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
