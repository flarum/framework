import Page from './Page';
import Checkbox from '../../common/components/Checkbox';
import Button from '../../common/components/Button';
import Alert from '../../common/components/Alert';
import Switch from '../../common/components/Switch';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ItemList from '../../common/utils/ItemList';
import icon from '../../common/helpers/icon';
import saveSettings from '../utils/saveSettings';

export default class AuthPage extends Page {
  init() {
    super.init();

    this.saving = false;
    this.refresh();
  }

  refresh() {
    this.loading = true;

    this.drivers = app.data.ssoDrivers;
    this.driverFields = this.driverFieldsList().toArray();
    this.driverInputs = [];

    this.fields = ['allow_sign_up', 'enable_user_pass_auth'];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach((key) => (this.values[key] = m.prop(settings[key])));

    for (const driver in this.drivers) {
      this.driverFields.forEach(field => {
        const fieldName = this.driverFieldKey(field.name, driver);

        this.fields.push(fieldName);
        this.values[fieldName] = m.prop(settings[fieldName]);

        this.driverInputs[fieldName] = new Checkbox({
          state: this.values[fieldName](),
          onchange: () => this.toggle(fieldName),
        });
      });
    }

    this.loading = false;
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

            <fieldset class="AuthPage-sso">
              <legend>{app.translator.trans('core.admin.auth.sso.heading')}</legend>
              <div className="helpText">{app.translator.trans('core.admin.auth.sso.help_text')}</div>
              {Object.keys(this.drivers).length > 0 ? (
                <table className="SsoGrid">
                  <thead>
                    <tr>
                      <td />
                      {this.driverFields.map((field) => (
                        <th className="SsoGrid-groupToggle">
                          {icon(field.icon)} {field.label}
                        </th>
                      ))}
                    </tr>
                  </thead>

                  <tbody>
                    {Object.keys(this.drivers).map((driver) => (
                      <tr>
                        <td className="SsoGrid-groupToggle">
                          {icon(this.drivers[driver].icon)} {this.drivers[driver].name || driver}
                        </td>
                        {this.driverFields.map((field) => (
                          <td className="SsoGrid-checkbox">{this.driverInputs[this.driverFieldKey(field.name, driver)].render()}</td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <div className="helpText">{app.translator.trans('core.admin.auth.no_sso_drivers_found')}</div>
              )}
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

  toggle(key) {
    this.values[key](!this.values[key]());

    this.driverInputs[key].props.state = this.values[key]();

    m.redraw();
  }

  driverFieldsList() {
    const items = new ItemList();

    items.add('enabled', {
      name: 'enabled',
      icon: 'fas fa-power-off',
      label: app.translator.trans('core.admin.auth.driver_fields.enabled'),
    });

    items.add('trust_emails', {
      name: 'trust_emails',
      icon: 'fas fa-envelope',
      label: app.translator.trans('core.admin.auth.driver_fields.trust_emails'),
    });

    return items;
  }

  driverFieldKey(field, driver) {
    return 'auth_driver_' + field + '_' + driver;
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
