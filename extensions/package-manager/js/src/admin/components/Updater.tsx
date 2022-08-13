import Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/helpers/humanTime';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import errorHandler from '../utils/errorHandler';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import MajorUpdater from './MajorUpdater';
import ExtensionItem from './ExtensionItem';
import extractText from 'flarum/common/utils/extractText';
import jumpToQueue from '../utils/jumpToQueue';
import { AsyncBackendResponse } from '../shims';
import { Extension } from 'flarum/admin/AdminApplication';

export type UpdatedPackage = {
  name: string;
  version: string;
  latest: string;
  'latest-minor': string | null;
  'latest-major': string | null;
  'latest-status': string;
  description: string;
};

export type ComposerUpdates = {
  installed: UpdatedPackage[];
};

export type LastUpdateCheck = {
  checkedAt: Date | null;
  updates: ComposerUpdates;
};

type UpdateType = 'major' | 'minor' | 'global';
type UpdateStatus = 'success' | 'failure' | null;
export type UpdateState = {
  ranAt: Date | null;
  status: UpdateStatus;
  limitedPackages: string[];
  incompatibleExtensions: string[];
};

export type LastUpdateRun = {
  [key in UpdateType]: UpdateState;
} & {
  limitedPackages: () => string[];
};

interface UpdaterAttrs extends ComponentAttrs {}

export default class Updater extends Component<UpdaterAttrs> {
  isLoading: string | null = null;
  packageUpdates: Record<string, UpdatedPackage> = {};
  lastUpdateCheck: LastUpdateCheck = JSON.parse(app.data.settings['flarum-package-manager.last_update_check']) as LastUpdateCheck;
  get lastUpdateRun(): LastUpdateRun {
    const lastUpdateRun = JSON.parse(app.data.settings['flarum-package-manager.last_update_run']) as LastUpdateRun;

    lastUpdateRun.limitedPackages = () => [
      ...lastUpdateRun.major.limitedPackages,
      ...lastUpdateRun.minor.limitedPackages,
      ...lastUpdateRun.global.limitedPackages,
    ];

    return lastUpdateRun;
  }

  oninit(vnode: Mithril.Vnode<UpdaterAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    const extensions = this.getExtensionUpdates();
    let coreUpdate: UpdatedPackage | undefined = this.getCoreUpdate();
    let core: any;

    if (coreUpdate) {
      core = {
        id: 'flarum-core',
        name: 'flarum/core',
        version: app.data.settings.version,
        icon: {
          backgroundImage: `url(${app.forum.attribute('baseUrl')}/assets/extensions/flarum-package-manager/flarum.svg`,
        },
        extra: {
          'flarum-extension': {
            title: app.translator.trans('flarum-package-manager.admin.updater.flarum'),
          },
        },
      };
    }

    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-package-manager.admin.updater.updater_title')}</label>
        <p className="helpText">{app.translator.trans('flarum-package-manager.admin.updater.updater_help')}</p>
        {this.lastUpdateCheck?.checkedAt && (
          <p className="PackageManager-lastUpdatedAt">
            <span className="PackageManager-lastUpdatedAt-label">
              {app.translator.trans('flarum-package-manager.admin.updater.last_update_checked_at')}
            </span>
            <span className="PackageManager-lastUpdatedAt-value">{humanTime(this.lastUpdateCheck.checkedAt)}</span>
          </p>
        )}
        <div className="PackageManager-updaterControls">
          <Button
            className="Button"
            icon="fas fa-sync-alt"
            onclick={this.checkForUpdates.bind(this)}
            loading={this.isLoading === 'check'}
            disabled={this.isLoading !== null && this.isLoading !== 'check'}
          >
            {app.translator.trans('flarum-package-manager.admin.updater.check_for_updates')}
          </Button>
          <Button
            className="Button"
            icon="fas fa-play"
            onclick={this.updateGlobally.bind(this)}
            loading={this.isLoading === 'global-update'}
            disabled={this.isLoading !== null && this.isLoading !== 'global-update'}
          >
            {app.translator.trans('flarum-package-manager.admin.updater.run_global_update')}
          </Button>
        </div>
        {this.isLoading !== null ? (
          <div className="PackageManager-extensions">
            <LoadingIndicator />
          </div>
        ) : extensions.length || core ? (
          <div className="PackageManager-extensions">
            <div className="PackageManager-extensions-grid">
              {core ? (
                <ExtensionItem
                  extension={core}
                  updates={coreUpdate}
                  isCore={true}
                  onClickUpdate={this.updateCoreMinor.bind(this)}
                  whyNotWarning={this.lastUpdateRun.limitedPackages().includes('flarum/core')}
                />
              ) : null}
              {extensions.map((extension: Extension) => (
                <ExtensionItem
                  extension={extension}
                  updates={this.packageUpdates[extension.id]}
                  onClickUpdate={this.updateExtension.bind(this, extension)}
                  whyNotWarning={this.lastUpdateRun.limitedPackages().includes(extension.name)}
                />
              ))}
            </div>
          </div>
        ) : null}
      </div>,
      coreUpdate && coreUpdate['latest-major'] ? <MajorUpdater coreUpdate={coreUpdate} updateState={this.lastUpdateRun.major} /> : null,
    ];
  }

  getExtensionUpdates(): Extension[] {
    this.lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => {
      const id = composerPackage.name.replace('/', '-').replace(/(flarum-ext-)|(flarum-)/, '');

      const extension = app.data.extensions[id];
      const safeToUpdate = ['semver-safe-update', 'update-possible'].includes(composerPackage['latest-status']);

      if (extension && safeToUpdate) {
        this.packageUpdates[extension.id] = composerPackage;
      }

      return extension && safeToUpdate;
    });

    return (Object.values(app.data.extensions) as Extension[]).filter((extension: Extension) => this.packageUpdates[extension.id]);
  }

  getCoreUpdate(): UpdatedPackage | undefined {
    return this.lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => composerPackage.name === 'flarum/core').pop();
  }

  checkForUpdates() {
    this.isLoading = 'check';

    app
      .request<AsyncBackendResponse | LastUpdateCheck>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/check-for-updates`,
        errorHandler,
      })
      .then((response) => {
        if ((response as AsyncBackendResponse).processing) {
          jumpToQueue();
        } else {
          this.lastUpdateCheck = response as LastUpdateCheck;
        }
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }

  updateCoreMinor() {
    if (confirm(extractText(app.translator.trans('flarum-package-manager.admin.minor_update_confirmation.content')))) {
      app.modal.show(LoadingModal);
      this.isLoading = 'minor-update';

      app
        .request<AsyncBackendResponse | null>({
          method: 'POST',
          url: `${app.forum.attribute('apiUrl')}/package-manager/minor-update`,
          errorHandler,
        })
        .then((response) => {
          if (response?.processing) {
            jumpToQueue();
          } else {
            app.alerts.show({ type: 'success' }, app.translator.trans('flarum-package-manager.admin.update_successful'));
            window.location.reload();
          }
        })
        .finally(() => {
          this.isLoading = null;
          m.redraw();
        });
    }
  }

  updateExtension(extension: any) {
    app.modal.show(LoadingModal);
    this.isLoading = 'extension-update';

    app
      .request<AsyncBackendResponse | null>({
        method: 'PATCH',
        url: `${app.forum.attribute('apiUrl')}/package-manager/extensions/${extension.id}`,
        errorHandler,
      })
      .then((response) => {
        if (response?.processing) {
          jumpToQueue();
        } else {
          app.alerts.show(
            { type: 'success' },
            app.translator.trans('flarum-package-manager.admin.extensions.successful_update', {
              extension: extension.extra['flarum-extension'].title,
            })
          );
          window.location.reload();
        }
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
      .request<AsyncBackendResponse | null>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/global-update`,
        errorHandler,
      })
      .then((response) => {
        if (response?.processing) {
          jumpToQueue();
        } else {
          app.alerts.show({ type: 'success' }, app.translator.trans('flarum-package-manager.admin.updater.global_update_successful'));
          window.location.reload();
        }
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }
}
