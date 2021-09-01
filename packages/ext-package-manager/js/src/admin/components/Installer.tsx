import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from "flarum/common/components/Button";
import Stream from "flarum/common/utils/Stream";

export default class Installer extends Component {
  packageName!: Stream<string>;
  isLoading: boolean = false;

  oninit(vnode: Mithril.Vnode): void {
    super.oninit(vnode);

    this.packageName = Stream('');
  }

  view(): Mithril.Children {
    return (
      <div className="Form-group">
        <label htmlFor="install-extension">{app.translator.trans('sycho-package-manager.admin.extensions.install')}</label>
        <p className="helpText">{app.translator.trans('sycho-package-manager.admin.extensions.install_help', {
          extiverse: <a href="https://extiverse.com">extiverse.com</a>
        })}</p>
        <div className="FormControl-container">
          <input className="FormControl" id="install-extension" placeholder="vendor/package-name" bidi={this.packageName}/>
          <Button className="Button" icon="fas fa-download" onclick={this.onsubmit.bind(this)} loading={this.isLoading}>
            {app.translator.trans('sycho-package-manager.admin.extensions.proceed')}
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
    this.isLoading = true;

    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/package-manager/extensions`,
      body: {
        data: this.data()
      },
    }).then(() => {
      this.isLoading = false;
      app.alerts.show({ type: 'success', message: app.translator.trans('core.lib.success')});
      m.redraw();
    }).catch(() => {
      this.isLoading = false;
      m.redraw();
    });
  }
}
