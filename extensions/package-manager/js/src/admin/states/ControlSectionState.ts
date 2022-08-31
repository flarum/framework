import app from 'flarum/admin/app';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import { UpdaterLoadingTypes } from '../components/Updater';
import { InstallerLoadingTypes } from '../components/Installer';
import { MajorUpdaterLoadingTypes } from '../components/MajorUpdater';
import { AsyncBackendResponse } from '../shims';
import errorHandler from '../utils/errorHandler';
import jumpToQueue from '../utils/jumpToQueue';
import { Extension } from 'flarum/admin/AdminApplication';
import extractText from 'flarum/common/utils/extractText';

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

export type LoadingTypes = UpdaterLoadingTypes | InstallerLoadingTypes | MajorUpdaterLoadingTypes;

export type CoreUpdate = {
  package: UpdatedPackage;
  extension: Extension;
};

export default class ControlSectionState {
  loading: LoadingTypes = null;

  public packageUpdates: Record<string, UpdatedPackage> = {};
  public lastUpdateCheck!: LastUpdateCheck;
  public extensionUpdates!: Extension[];
  public coreUpdate: CoreUpdate | null = null;
  get lastUpdateRun(): LastUpdateRun {
    const lastUpdateRun = JSON.parse(app.data.settings['flarum-package-manager.last_update_run']) as LastUpdateRun;

    lastUpdateRun.limitedPackages = () => [
      ...lastUpdateRun.major.limitedPackages,
      ...lastUpdateRun.minor.limitedPackages,
      ...lastUpdateRun.global.limitedPackages,
    ];

    return lastUpdateRun;
  }

  constructor() {
    this.lastUpdateCheck = JSON.parse(app.data.settings['flarum-package-manager.last_update_check']) as LastUpdateCheck;
    this.extensionUpdates = this.formatExtensionUpdates(this.lastUpdateCheck);
    this.coreUpdate = this.formatCoreUpdate(this.lastUpdateCheck);
  }

  isLoading(name: LoadingTypes = null): boolean {
    return (name && this.loading === name) || (!name && this.loading !== null);
  }

  isLoadingOtherThan(name: LoadingTypes): boolean {
    return this.loading !== null && this.loading !== name;
  }

  setLoading(name: LoadingTypes): void {
    this.loading = name;
  }

  checkForUpdates() {
    this.setLoading('check');

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
          this.extensionUpdates = this.formatExtensionUpdates(response as LastUpdateCheck);
          this.coreUpdate = this.formatCoreUpdate(response as LastUpdateCheck);
          m.redraw();
        }
      })
      .finally(() => {
        this.setLoading(null);
        m.redraw();
      });
  }

  updateCoreMinor() {
    if (confirm(extractText(app.translator.trans('flarum-package-manager.admin.minor_update_confirmation.content')))) {
      app.modal.show(LoadingModal);
      this.setLoading('minor-update');

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
          this.setLoading(null);
          app.modal.close();
          m.redraw();
        });
    }
  }

  updateExtension(extension: Extension) {
    app.modal.show(LoadingModal);
    this.setLoading('extension-update');

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
        this.setLoading(null);
        app.modal.close();
        m.redraw();
      });
  }

  updateGlobally() {
    app.modal.show(LoadingModal);
    this.setLoading('global-update');

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
        this.setLoading(null);
        app.modal.close();
        m.redraw();
      });
  }

  formatExtensionUpdates(lastUpdateCheck: LastUpdateCheck): Extension[] {
    this.packageUpdates = {};

    lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => {
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

  formatCoreUpdate(lastUpdateCheck: LastUpdateCheck): CoreUpdate | null {
    const core = lastUpdateCheck?.updates?.installed?.filter((composerPackage: UpdatedPackage) => composerPackage.name === 'flarum/core').pop();

    if (!core) return null;

    return {
      package: core,
      extension: {
        id: 'flarum-core',
        name: 'flarum/core',
        version: app.data.settings.version,
        icon: {
          // @ts-ignore
          backgroundImage: `url(${app.forum.attribute('baseUrl')}/assets/extensions/flarum-package-manager/flarum.svg`,
        },
        extra: {
          'flarum-extension': {
            title: extractText(app.translator.trans('flarum-package-manager.admin.updater.flarum')),
          },
        },
      },
    };
  }
}
