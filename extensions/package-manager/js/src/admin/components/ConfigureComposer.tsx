import app from 'flarum/admin/app';
import type Mithril from 'mithril';
import ConfigureJson, { type IConfigureJson } from './ConfigureJson';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import RepositoryModal from './RepositoryModal';

export type Repository = {
  type: 'composer' | 'vcs' | 'path';
  url: string;
};

export default class ConfigureComposer extends ConfigureJson<IConfigureJson> {
  protected type = 'composer';

  title(): Mithril.Children {
    return app.translator.trans('flarum-extension-manager.admin.composer.title');
  }

  className(): string {
    return 'ConfigureComposer';
  }

  content(): Mithril.Children {
    return (
      <div className="ExtensionManager-SettingsGroups-content">
        {this.attrs.buildSettingComponent.call(this, {
          setting: 'minimum-stability',
          label: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.label'),
          help: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.help'),
          type: 'select',
          options: {
            stable: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.stable'),
            RC: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.rc'),
            beta: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.beta'),
            alpha: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.alpha'),
            dev: app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.dev'),
          },
        })}
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.label')}</label>
          <div className="helpText">{app.translator.trans('flarum-extension-manager.admin.composer.repositories.help')}</div>
          <div className="ConfigureComposer-repositories">
            {Object.keys(this.setting('repositories')() || {}).map((name) => {
              const repository = this.setting('repositories')()[name] as Repository;

              return (
                <div className="ButtonGroup ButtonGroup--full">
                  <Button
                    className="Button"
                    icon={
                      {
                        composer: 'fas fa-cubes',
                        vcs: 'fas fa-code-branch',
                        path: 'fas fa-folder',
                      }[repository.type]
                    }
                    onclick={() =>
                      app.modal.show(RepositoryModal, {
                        name,
                        repository,
                        onsubmit: (repository: Repository, newName: string) => {
                          const repositories = this.setting('repositories')();
                          delete repositories[name];

                          this.setting('repositories')(repositories);

                          this.onchange(repository, newName);
                        },
                      })
                    }
                  >
                    {name} ({repository.type})
                  </Button>
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-trash"
                    aria-label={app.translator.trans('flarum-extension-manager.admin.composer.delete_repository_label')}
                    onclick={() => {
                      if (confirm(extractText(app.translator.trans('flarum-extension-manager.admin.composer.delete_repository_confirmation')))) {
                        const repositories = { ...this.setting('repositories')() };
                        delete repositories[name];

                        this.setting('repositories')(repositories);
                      }
                    }}
                  />
                </div>
              );
            })}
          </div>
        </div>
      </div>
    );
  }

  submitButton(): Mithril.Children[] {
    const items = super.submitButton();

    items.push(
      <Button className="Button" onclick={() => app.modal.show(RepositoryModal, { onsubmit: this.onchange.bind(this) })}>
        {app.translator.trans('flarum-extension-manager.admin.composer.add_repository_label')}
      </Button>
    );

    return items;
  }

  onchange(repository: Repository, name: string) {
    this.setting('repositories')({
      ...this.setting('repositories')(),
      [name]: repository,
    });
  }
}
