import Page from './Page';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import Switch from '../../common/components/Switch';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import saveSettings from '../utils/saveSettings';

export default class AuthPage extends Page {
  init() {
    super.init();

    this.saving = false;
    this.refresh();
  }

  refresh() {
    this.loading = true;

    this.drivers = [];
    this.fields = ['allow_sign_up', 'enable_user_pass_auth'];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach((key) => (this.values[key] = m.prop(settings[key])));

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/auth/settings',
      })
      .then((response) => {
        this.drivers = response['data']['attributes']['drivers'];
        for (const driver in this.drivers) {
          for (const field in this.drivers[driver]) {
            const fieldName = 'auth_driver_' + field + '_' + driver;
            this.fields.push(fieldName);
            this.values[fieldName] = m.prop(settings[fieldName]);
          }
        }

        this.loading = false;
        m.redraw();
      });
  }

  view() {
    if (this.loading || this.saving) {
      return (
        <div className="AuthPage">
          <div className="container">
            <LoadingIndicator />
          </div>
        </div>
      );
    }

    const fields = this.drivers;
    const fieldKeys = Object.keys(fields);

    return (
      <div className="AuthPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <h2>{app.translator.trans('core.admin.auth.heading')}</h2>
            <div className="helpText">{app.translator.trans('core.admin.auth.text')}</div>

            <fieldset class="AuthPage-settings">
              {Switch.component({
                state: this.values.enable_user_pass_auth(),
                onchange: this.values.enable_user_pass_auth,
                children: app.translator.trans('core.admin.auth.enable_user_pass_auth_label'),
              })}
              {Switch.component({
                state: this.values.allow_sign_up(),
                onchange: this.values.allow_sign_up,
                children: app.translator.trans('core.admin.auth.allow_sign_up_label'),
              })}
            </fieldset>

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.auth.submit_button'),
              disabled: !this.changed(),
            })}
          </form>
        </div>
      </div>
    );
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
