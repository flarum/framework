import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import icon from "flarum/common/helpers/icon";
import Button from "flarum/common/components/Button";
import humanTime from "flarum/common/helpers/humanTime";
import LoadingModal from "flarum/admin/components/LoadingModal";
import ComposerFailureModal from "./ComposerFailureModal";
import Tooltip from "flarum/common/components/Tooltip";

type UpdatedPackage = {
  name: string;
  version: string;
  latest: string;
  "latest-status": string;
  description: string;
};

type ComposerUpdates = {
  installed: UpdatedPackage[];
};

type LastUpdateCheck = {
  checkedAt: Date;
  updates: ComposerUpdates;
};

export default class Updater extends Component {
  isLoading: boolean = false;
  lastUpdateCheck: LastUpdateCheck = app.data.lastUpdateCheck || {};

  oninit(vnode) {
    super.oninit(vnode);
  }

  view() {
    const extensions: any = this.getExtensionUpdates();

    // @TODO catch `flarum/core` updates and display them differently, since it is the CORE and not an extension.

    return (
      <div className="Form-group">
        <label>{app.translator.trans('sycho-package-manager.admin.updater.updater_title')}</label>
        <p className="helpText">{app.translator.trans('sycho-package-manager.admin.updater.updater_help')}</p>
        {Object.keys(this.lastUpdateCheck).length ? (
          <p className="PackageManager-lastUpdatedAt">
            <span className="PackageManager-lastUpdatedAt-label">{app.translator.trans('sycho-package-manager.admin.updater.last_update_checked_at')}</span>
            <span className="PackageManager-lastUpdatedAt-value">{humanTime(this.lastUpdateCheck?.checkedAt)}</span>
          </p>
        ) : null}
        <Button
          className="Button"
          icon="fas fa-sync-alt"
          onclick={this.checkForUpdates.bind(this)}
          loading={this.isLoading}>
          {app.translator.trans('sycho-package-manager.admin.updater.check_for_updates')}
        </Button>
        {extensions.length ? (
          <div className="PackageManager-extensions">
            <div className="PackageManager-extensions-grid">
              {extensions.map((extension: any) => (
                <div className="PackageManager-extension">
                  <div className="PackageManager-extension-icon ExtensionIcon" style={extension.icon}>
                    {extension.icon ? icon(extension.icon.name) : ''}
                  </div>
                  <div className="PackageManager-extension-info">
                    <div className="PackageManager-extension-name">{extension.extra['flarum-extension'].title}</div>
                    <div className="PackageManager-extension-version">
                      <span className="PackageManager-extension-version-current">{extension.version}</span>
                      <span className="PackageManager-extension-version-latest Label">{extension.newPackageUpdate.latest}</span>
                    </div>
                  </div>
                  <div className="PackageManager-extension-controls">
                    <Tooltip text={app.translator.trans('sycho-package-manager.admin.extensions.update')}>
                      <Button
                        icon="fas fa-arrow-alt-circle-up"
                        className="Button Button--icon Button--flat"
                        onclick={this.update.bind(this, extension)}
                        aria-label={app.translator.trans('sycho-package-manager.admin.extensions.update')} />
                    </Tooltip>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : null}
      </div>
    );
  }

  getExtensionUpdates() {
    const updates = this.lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => {
      const extension = app.data.extensions[composerPackage.name.replace('/', '-').replace(/(flarum-ext-)|(flarum-)/, '')];
      const safeToUpdate = ['semver-safe-update', 'update-possible'].includes(composerPackage['latest-status']);

      if (extension && safeToUpdate) {
        extension.newPackageUpdate = composerPackage;
      }

      return extension && safeToUpdate;
    });

    return Object.values(app.data.extensions).filter((extension: any) => extension.newPackageUpdate);
  }

  checkForUpdates() {
    this.isLoading = true;

    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/package-manager/check-for-updates`,
    }).then((response) => {
      this.isLoading = false;
      this.lastUpdateCheck = response as LastUpdateCheck;
      m.redraw();
    });
  }

  update(extension: any) {
    app.modal.show(LoadingModal);

    app.request({
      method: 'PATCH',
      url: `${app.forum.attribute('apiUrl')}/package-manager/extensions/${extension.id}`,
      errorHandler: (e: any) => {
        const error = e.response.errors[0];

        if (error.code !== 'composer_command_failure') {
          throw e;
        }

        app.modal.show(ComposerFailureModal, { error });
      },
    }).then(() => {
      app.alerts.show({ type: 'success' }, app.translator.trans('sycho-package-manager.admin.extensions.successful_update', { extension: extension.extra['flarum-extension'].title }));
      window.location.reload();
    }).finally(() => {
      m.redraw();
    });
  }
}
