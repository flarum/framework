import app from 'flarum/admin/app';
import type Mithril from 'mithril';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import { CommonSettingsItemOptions, type SettingsComponentOptions } from '@flarum/core/src/admin/components/AdminPage';
import AdminPage from 'flarum/admin/components/AdminPage';
import type ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';
import classList from 'flarum/common/utils/classList';

export interface IConfigureJson extends ComponentAttrs {
  buildSettingComponent: (entry: ((this: this) => Mithril.Children) | SettingsComponentOptions) => Mithril.Children;
}

export default abstract class ConfigureJson<CustomAttrs extends IConfigureJson = IConfigureJson> extends Component<CustomAttrs> {
  protected settings: Record<string, Stream<any>> = {};
  protected initialSettings: Record<string, any> | null = null;
  protected loading: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.submit(true);
  }

  protected abstract type: string;
  abstract title(): Mithril.Children;
  abstract content(): Mithril.Children;

  className(): string {
    return '';
  }

  view(): Mithril.Children {
    return (
      <div className={classList('Form', this.className())}>
        <label>{this.title()}</label>
        {this.content()}
        <div className="Form-group Form--controls">{this.submitButton()}</div>
      </div>
    );
  }

  submitButton(): Mithril.Children[] {
    return [
      <Button className="Button Button--primary" loading={this.loading} onclick={() => this.submit(false)} disabled={!this.isDirty()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>,
    ];
  }

  customSettingComponents(): ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children> {
    return AdminPage.prototype.customSettingComponents();
  }

  setting(key: string) {
    return this.settings[key] ?? (this.settings[key] = Stream());
  }

  submit(readOnly: boolean) {
    this.loading = true;

    const configuration: any = {};

    Object.keys(this.settings).forEach((key) => {
      configuration[key] = this.settings[key]();
    });

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/extension-manager/composer',
        body: {
          type: this.type,
          data: readOnly ? null : configuration,
        },
      })
      .then(({ data }: any) => {
        Object.keys(data).forEach((key) => {
          this.settings[key] = Stream(data[key]);
        });

        this.initialSettings = Array.isArray(data) ? {} : data;
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  isDirty() {
    return JSON.stringify(this.initialSettings) !== JSON.stringify(this.settings);
  }
}
