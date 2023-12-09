import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';

export interface InstallerAttrs extends ComponentAttrs {}

export type InstallerLoadingTypes = 'extension-install' | null;

export default class Installer extends Component<InstallerAttrs> {
  packageName!: Stream<string>;

  oninit(vnode: Mithril.Vnode<InstallerAttrs, this>): void {
    super.oninit(vnode);

    this.packageName = Stream('');
  }

  view(): Mithril.Children {
    return (
      <div className="Form-group PackageManager-installer">
        <label htmlFor="install-extension">{app.translator.trans('flarum-package-manager.admin.extensions.install')}</label>
        <p className="helpText">
          {app.translator.trans('flarum-package-manager.admin.extensions.install_help', {
            extiverse: <a href="https://extiverse.com">extiverse.com</a>,
          })}
        </p>
        <div className="FormControl-container">
          <input className="FormControl" id="install-extension" placeholder="vendor/package-name" bidi={this.packageName} />
          <Button
            className="Button"
            icon="fas fa-download"
            onclick={this.onsubmit.bind(this)}
            loading={app.packageManager.control.isLoading('extension-install')}
            disabled={app.packageManager.control.isLoading()}
          >
            {app.translator.trans('flarum-package-manager.admin.extensions.proceed')}
          </Button>
        </div>
      </div>
    );
  }

  data(): any {
    return {
      package: this.packageName(),
    };
  }

  onsubmit(): void {
    app.packageManager.control.requirePackage(this.data());
  }
}
