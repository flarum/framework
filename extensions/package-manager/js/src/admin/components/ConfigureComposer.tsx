import app from 'flarum/admin/app';
import type Mithril from 'mithril';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import { CommonSettingsItemOptions, type SettingsComponentOptions } from '@flarum/core/src/admin/components/AdminPage';
import AdminPage from 'flarum/admin/components/AdminPage';
import type ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';

export interface IConfigureComposer extends ComponentAttrs {
  buildSettingComponent: (entry: ((this: this) => Mithril.Children) | SettingsComponentOptions) => Mithril.Children;
}

export default class ConfigureComposer<CustomAttrs extends IConfigureComposer = IConfigureComposer> extends Component<CustomAttrs> {
  protected settings: Record<string, Stream<any>> = {};
  protected loading: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.submit();
  }

  view(): Mithril.Children {
    return (
      <div className="Form">
        {this.attrs.buildSettingComponent.call(this, {
          setting: 'minimum-stability',
          label: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.label'),
          help: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.help'),
          type: 'select',
          options: {
            stable: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.options.stable'),
            RC: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.options.rc'),
            beta: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.options.beta'),
            alpha: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.options.alpha'),
            dev: app.translator.trans('flarum-package-manager.admin.composer.minimum_stability.options.dev'),
          },
        })}

        <div className="Form-group">
          <Button className="Button Button--primary" loading={this.loading} onclick={this.submit.bind(this)}>
            {app.translator.trans('core.admin.settings.submit_button')}
          </Button>
        </div>
      </div>
    );
  }

  customSettingComponents(): ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children> {
    return AdminPage.prototype.customSettingComponents();
  }

  setting(key: string) {
    return this.settings[key] ?? (() => null);
  }

  submit() {
    this.loading = true;

    const configuration: any = {};

    Object.keys(this.settings).forEach((key) => {
      configuration[key] = this.settings[key]();
    });

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/package-manager/composer',
        body: { data: configuration },
      })
      .then(({ data }: any) => {
        Object.keys(data).forEach((key) => {
          this.settings[key] = Stream(data[key]);
        });
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
