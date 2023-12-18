import app from 'flarum/admin/app';
import type Mithril from 'mithril';
import ConfigureJson, { IConfigureJson } from './ConfigureJson';
import Button from 'flarum/common/components/Button';
import AuthMethodModal from './AuthMethodModal';
import extractText from 'flarum/common/utils/extractText';

export default class ConfigureAuth extends ConfigureJson<IConfigureJson> {
  protected type = 'auth';

  title(): Mithril.Children {
    return app.translator.trans('flarum-package-manager.admin.auth_config.title');
  }

  className(): string {
    return 'ConfigureAuth';
  }

  content(): Mithril.Children {
    const authSettings = Object.keys(this.settings);

    return (
      <div className="SettingsGroups-content">
        {authSettings.length ? (
          authSettings.map((type) => {
            const hosts = this.settings[type]();

            return (
              <div className="Form-group">
                <label>{app.translator.trans(`flarum-package-manager.admin.auth_config.types.${type}`)}</label>
                <div className="ConfigureAuth-hosts">
                  {Object.keys(hosts).map((host) => {
                    const data = hosts[host] as string | Record<string, string>;

                    return (
                      <div className="ButtonGroup ButtonGroup--full">
                        <Button
                          className="Button"
                          icon="fas fa-key"
                          onclick={() =>
                            app.modal.show(AuthMethodModal, {
                              type,
                              host,
                              token: data,
                              onsubmit: this.onchange.bind(this),
                            })
                          }
                        >
                          {host}
                        </Button>
                        <Button
                          className="Button Button--icon"
                          icon="fas fa-trash"
                          aria-label={app.translator.trans('flarum-package-manager.admin.auth_config.delete_label')}
                          onclick={() => {
                            if (confirm(extractText(app.translator.trans('flarum-package-manager.admin.auth_config.delete_confirmation')))) {
                              const newType = { ...this.setting(type)() };
                              delete newType[host];

                              if (Object.keys(newType).length) {
                                this.setting(type)(newType);
                              } else {
                                delete this.settings[type];
                              }
                            }
                          }}
                        />
                      </div>
                    );
                  })}
                </div>
              </div>
            );
          })
        ) : (
          <span className="helpText">{app.translator.trans('flarum-package-manager.admin.auth_config.no_auth_methods_configured')}</span>
        )}
      </div>
    );
  }

  submitButton(): Mithril.Children[] {
    const items = super.submitButton();

    items.push(
      <Button
        className="Button"
        loading={this.loading}
        onclick={() =>
          app.modal.show(AuthMethodModal, {
            onsubmit: this.onchange.bind(this),
          })
        }
      >
        {app.translator.trans('flarum-package-manager.admin.auth_config.add_label')}
      </Button>
    );

    return items;
  }

  onchange(type: string, host: string, token: string) {
    this.setting(type)({ ...this.setting(type)(), [host]: token });
  }
}
