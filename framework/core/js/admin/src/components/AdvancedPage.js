import Page from 'flarum/components/Page';
import FieldSet from 'flarum/components/FieldSet';
import Button from 'flarum/components/Button';
import Alert from 'flarum/components/Alert';
import saveSettings from 'flarum/utils/saveSettings';
import ItemList from 'flarum/utils/ItemList';

export default class AdvancedPage extends Page {
  init() {
    super.init();

    this.loading = false;

    this.fields = [
      'mail_driver',
      'mail_host',
      'mail_from',
      'mail_port',
      'mail_username',
      'mail_password',
      'mail_encryption'
    ];
    this.values = {};

    const settings = app.settings;
    this.fields.forEach(key => this.values[key] = m.prop(settings[key]));

    this.localeOptions = {};
    const locales = app.locales;
    for (const i in locales) {
      this.localeOptions[i] = `${locales[i]} (${i})`;
    }
  }

  view() {
    return (
      <div className="AdvancedPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            {FieldSet.component({
              label: app.translator.trans('core.admin.advanced.mail_heading'),
              className: 'AdvancedPage-MailSettings',
              children: [
                <div className="helpText">
                  {app.translator.trans('core.admin.advanced.mail_text')}
                </div>,
                <div className="AdvancedPage-mailSettings-input">
                  <input className="FormControl" value={this.values.mail_driver() || ''} oninput={m.withAttr('value', this.values.mail_driver)} placeholder={app.translator.trans('core.admin.advanced.mail_driver')}/>
                  <input className="FormControl" value={this.values.mail_host() || ''} oninput={m.withAttr('value', this.values.mail_host)} placeholder={app.translator.trans('core.admin.advanced.mail_host')}/>
                  <input className="FormControl" value={this.values.mail_from() || ''} oninput={m.withAttr('value', this.values.mail_from)} placeholder={app.translator.trans('core.admin.advanced.mail_from')}/>
                  <input className="FormControl" value={this.values.mail_port() || ''} oninput={m.withAttr('value', this.values.mail_port)} placeholder={app.translator.trans('core.admin.advanced.mail_port')}/>
                  <input className="FormControl" value={this.values.mail_username() || ''} oninput={m.withAttr('value', this.values.mail_username)} placeholder={app.translator.trans('core.admin.advanced.mail_username')}/>
                  <input className="FormControl" value={this.values.mail_password() || ''} oninput={m.withAttr('value', this.values.mail_password)} placeholder={app.translator.trans('core.admin.advanced.mail_password')}/>
                  <input className="FormControl" value={this.values.mail_encryption() || ''} oninput={m.withAttr('value', this.values.mail_encryption)} placeholder={app.translator.trans('core.admin.advanced.mail_encryption')}/>
                </div>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.advanced.submit_button'),
              loading: this.loading,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    );
  }

  changed() {
    return this.fields.some(key => this.values[key]() !== app.settings[key]);
  }

  onsubmit(e) {
    e.preventDefault();

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
