import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import Button from 'flarum/common/components/Button';
import Tooltip from 'flarum/common/components/Tooltip';
import { UpdatedPackage } from './Updater';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import errorHandler from '../utils/errorHandler';

interface MajorUpdaterAttrs extends ComponentAttrs {
  coreUpdate: UpdatedPackage;
}

export default class MajorUpdater<T extends MajorUpdaterAttrs = MajorUpdaterAttrs> extends Component<T> {
  isLoading: string | null = null;

  view(vnode: Mithril.Vnode<ComponentAttrs, this>): Mithril.Children {
    return (
      <div className="Form-group PackageManager-majorUpdate">
        <img alt="flarum logo" src={app.forum.attribute('baseUrl') + '/assets/extensions/flarum-package-manager/flarum.svg'} />
        <label>{app.translator.trans('flarum-package-manager.admin.major_updater.title', { version: this.attrs.coreUpdate['latest-major'] })}</label>
        <p className="helpText">{app.translator.trans('flarum-package-manager.admin.major_updater.description')}</p>
        <div className="PackageManager-updaterControls">
          <Tooltip text={app.translator.trans('flarum-package-manager.admin.major_updater.dry_run_help')}>
            <Button className="Button" icon="fas fa-vial" onclick={this.update.bind(this, true)}>
              {app.translator.trans('flarum-package-manager.admin.major_updater.dry_run')}
            </Button>
          </Tooltip>
          <Button className="Button" icon="fas fa-play" onclick={this.update.bind(this, false)}>
            {app.translator.trans('flarum-package-manager.admin.major_updater.update')}
          </Button>
        </div>
      </div>
    );
  }

  update(dryRun: boolean) {
    this.isLoading = `update-${dryRun ? 'dry-run' : 'run'}`;
    app.modal.show(LoadingModal);

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/major-update`,
        body: {
          data: { dryRun },
        },
        errorHandler,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('flarum-package-manager.admin.update_successful'));
        window.location.reload();
      })
      .finally(() => {
        this.isLoading = null;
        m.redraw();
      });
  }
}
