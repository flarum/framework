import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/helpers/humanTime';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import MajorUpdater from './MajorUpdater';
import ExtensionItem from './ExtensionItem';
import { Extension } from 'flarum/admin/AdminApplication';
import Alert from 'flarum/common/components/Alert';
import ItemList from 'flarum/common/utils/ItemList';

export interface IUpdaterAttrs extends ComponentAttrs {}

export type UpdaterLoadingTypes = 'check' | 'minor-update' | 'global-update' | 'extension-update' | null;

export default class Updater extends Component<IUpdaterAttrs> {
  view() {
    const core = app.packageManager.control.coreUpdate;

    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-package-manager.admin.updater.updater_title')}</label>
        <p className="helpText">{app.translator.trans('flarum-package-manager.admin.updater.updater_help')}</p>
        {this.lastUpdateCheckView()}
        <div className="PackageManager-updaterControls">{this.controlItems().toArray()}</div>
        {this.availableUpdatesView()}
      </div>,
      core && core.package['latest-major'] ? (
        <MajorUpdater coreUpdate={core.package} updateState={app.packageManager.control.lastUpdateRun.major} />
      ) : null,
    ];
  }

  lastUpdateCheckView() {
    return (
      (app.packageManager.control.lastUpdateCheck?.checkedAt && (
        <p className="PackageManager-lastUpdatedAt">
          <span className="PackageManager-lastUpdatedAt-label">
            {app.translator.trans('flarum-package-manager.admin.updater.last_update_checked_at')}
          </span>
          <span className="PackageManager-lastUpdatedAt-value">{humanTime(app.packageManager.control.lastUpdateCheck.checkedAt)}</span>
        </p>
      )) ||
      null
    );
  }

  availableUpdatesView() {
    const state = app.packageManager.control;

    if (app.packageManager.control.isLoading()) {
      return (
        <div className="PackageManager-extensions">
          <LoadingIndicator />
        </div>
      );
    }

    if (!(state.extensionUpdates.length || state.coreUpdate)) {
      return (
        <div className="PackageManager-extensions">
          <Alert type="success" dismissible={false}>
            {app.translator.trans('flarum-package-manager.admin.updater.up_to_date')}
          </Alert>
        </div>
      );
    }

    return (
      <div className="PackageManager-extensions">
        <div className="PackageManager-extensions-grid">
          {state.coreUpdate ? (
            <ExtensionItem
              extension={state.coreUpdate.extension}
              updates={state.coreUpdate.package}
              isCore={true}
              onClickUpdate={() => state.updateCoreMinor()}
              whyNotWarning={state.lastUpdateRun.limitedPackages().includes('flarum/core')}
            />
          ) : null}
          {state.extensionUpdates.map((extension: Extension) => (
            <ExtensionItem
              extension={extension}
              updates={state.packageUpdates[extension.id]}
              onClickUpdate={() => state.updateExtension(extension)}
              whyNotWarning={state.lastUpdateRun.limitedPackages().includes(extension.name)}
            />
          ))}
        </div>
      </div>
    );
  }

  controlItems() {
    const items = new ItemList();

    items.add(
      'updateCheck',
      <Button
        className="Button"
        icon="fas fa-sync-alt"
        onclick={() => app.packageManager.control.checkForUpdates()}
        loading={app.packageManager.control.isLoading('check')}
        disabled={app.packageManager.control.isLoadingOtherThan('check')}
      >
        {app.translator.trans('flarum-package-manager.admin.updater.check_for_updates')}
      </Button>,
      100
    );

    items.add(
      'globalUpdate',
      <Button
        className="Button"
        icon="fas fa-play"
        onclick={() => app.packageManager.control.updateGlobally()}
        loading={app.packageManager.control.isLoading('global-update')}
        disabled={app.packageManager.control.isLoadingOtherThan('global-update')}
      >
        {app.translator.trans('flarum-package-manager.admin.updater.run_global_update')}
      </Button>
    );

    return items;
  }
}
