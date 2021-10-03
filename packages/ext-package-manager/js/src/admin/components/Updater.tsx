import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/helpers/humanTime';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import Tooltip from 'flarum/common/components/Tooltip';
import errorHandler from '../utils/errorHandler';
import classList from 'flarum/common/utils/classList';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import MajorUpdater from './MajorUpdater';

export type UpdatedPackage = {
  name: string;
  version: string;
  latest: string;
  'latest-minor': string | null;
  'latest-major': string | null;
  'latest-status': string;
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
  isLoading: string | null = null;
  lastUpdateCheck: LastUpdateCheck = app.data.lastUpdateCheck || {};

  oninit(vnode) {
    super.oninit(vnode);
  }

  view() {
    const extensions: any = this.getExtensionUpdates();
    const coreUpdate: UpdatedPackage | undefined = this.getCoreUpdate();
    let core = null;

    if (coreUpdate) {
      core = {
        title: app.translator.trans('sycho-package-manager.admin.updater.flarum'),
        version: app.data.settings.version,
        icon: {
          backgroundImage: `url(${app.forum.attribute('baseUrl')}/assets/extensions/sycho-package-manager/flarum.svg`,
        },
        newPackageUpdate: coreUpdate,
      };
    }

    return [
      <div className="Form-group">
        <label>{app.translator.trans('sycho-package-manager.admin.updater.updater_title')}</label>
        <p className="helpText">{app.translator.trans('sycho-package-manager.admin.updater.updater_help')}</p>
        {Object.keys(this.lastUpdateCheck).length ? (
          <p className="PackageManager-lastUpdatedAt">
            <span className="PackageManager-lastUpdatedAt-label">
              {app.translator.trans('sycho-package-manager.admin.updater.last_update_checked_at')}
            </span>
            <span className="PackageManager-lastUpdatedAt-value">{humanTime(this.lastUpdateCheck?.checkedAt)}</span>
          </p>
        ) : null}
        <div className="PackageManager-updaterControls">
          <Button
            className="Button"
            icon="fas fa-sync-alt"
            onclick={this.checkForUpdates.bind(this)}
            loading={this.isLoading === 'check'}
            disabled={this.isLoading !== null && this.isLoading !== 'check'}
          >
            {app.translator.trans('sycho-package-manager.admin.updater.check_for_updates')}
          </Button>
          <Button
            className="Button"
            icon="fas fa-play"
            onclick={this.updateGlobally.bind(this)}
            loading={this.isLoading === 'global-update'}
            disabled={this.isLoading !== null && this.isLoading !== 'global-update'}
          >
            {app.translator.trans('sycho-package-manager.admin.updater.run_global_update')}
          </Button>
        </div>
        {this.isLoading !== null ? (
          <div className="PackageManager-extensions">
            <LoadingIndicator />
          </div>
        ) : extensions.length || core ? (
          <div className="PackageManager-extensions">
            <div className="PackageManager-extensions-grid">
              {core ? this.extensionItem(core, true) : null}
              {extensions.map((extension: any) => this.extensionItem(extension))}
            </div>
          </div>
        ) : null}
      </div>,
      coreUpdate && coreUpdate['latest-major'] ? <MajorUpdater coreUpdate={coreUpdate} /> : null,
    ];
  }

  extensionItem(extension: any, isCore: boolean = false) {
    return (
      <div
        className={classList({
          'PackageManager-extension': true,
          'PackageManager-extension--core': isCore,
        })}
      >
        <div className="PackageManager-extension-icon ExtensionIcon" style={extension.icon}>
          {extension.icon ? icon(extension.icon.name) : ''}
        </div>
        <div className="PackageManager-extension-info">
          <div className="PackageManager-extension-name">{extension.title || extension.extra['flarum-extension'].title}</div>
          <div className="PackageManager-extension-version">
            <span className="PackageManager-extension-version-current">{this.version(extension.version)}</span>
            {extension.newPackageUpdate['latest-minor'] ? (
              <span className="PackageManager-extension-version-latest PackageManager-extension-version-latest--minor">
                {this.version(extension.newPackageUpdate['latest-minor'])}
              </span>
            ) : null}
            {extension.newPackageUpdate['latest-major'] && !isCore ? (
              <span className="PackageManager-extension-version-latest PackageManager-extension-version-latest--major">
                {this.version(extension.newPackageUpdate['latest-major'])}
              </span>
            ) : null}
          </div>
        </div>
        <div className="PackageManager-extension-controls">
          <Tooltip text={app.translator.trans('sycho-package-manager.admin.extensions.update')}>
            <Button
              icon="fas fa-arrow-alt-circle-up"
              className="Button Button--icon Button--flat"
              onclick={isCore ? this.updateCoreMinor.bind(this) : this.updateExtension.bind(this, extension)}
              aria-label={app.translator.trans('sycho-package-manager.admin.extensions.update')}
            />
          </Tooltip>
        </div>
      </div>
    );
  }

  version(v: string) {
    return 'v' + v.replace('v', '');
  }

  getExtensionUpdates() {
    this.lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => {
      const extension = app.data.extensions[composerPackage.name.replace('/', '-').replace(/(flarum-ext-)|(flarum-)/, '')];
      const safeToUpdate = ['semver-safe-update', 'update-possible'].includes(composerPackage['latest-status']);

      if (extension && safeToUpdate) {
        extension.newPackageUpdate = composerPackage;
      }

      return extension && safeToUpdate;
    });

    return Object.values(app.data.extensions).filter((extension: any) => extension.newPackageUpdate);
  }

  getCoreUpdate(): UpdatedPackage | undefined {
    return this.lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => composerPackage.name === 'flarum/core').pop();
  }

  checkForUpdates() {
    this.isLoading = 'check';

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/check-for-updates`,
        errorHandler,
      })
      .then((response) => {
        this.lastUpdateCheck = response as LastUpdateCheck;
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }

  updateCoreMinor() {
    app.modal.show(LoadingModal);
    this.isLoading = 'minor-update';

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/minor-update`,
        errorHandler,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('sycho-package-manager.admin.update_successful'));
        window.location.reload();
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }

  updateExtension(extension: any) {
    app.modal.show(LoadingModal);
    this.isLoading = 'extension-update';

    app
      .request({
        method: 'PATCH',
        url: `${app.forum.attribute('apiUrl')}/package-manager/extensions/${extension.id}`,
        errorHandler,
      })
      .then(() => {
        app.alerts.show(
          { type: 'success' },
          app.translator.trans('sycho-package-manager.admin.extensions.successful_update', { extension: extension.extra['flarum-extension'].title })
        );
        window.location.reload();
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }

  updateGlobally() {
    app.modal.show(LoadingModal);
    this.isLoading = 'global-update';

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/global-update`,
        errorHandler,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('sycho-package-manager.admin.updater.global_update_successful'));
        window.location.reload();
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }
}
