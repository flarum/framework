import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import Button from 'flarum/common/components/Button';
import Tooltip from 'flarum/common/components/Tooltip';
import { UpdatedPackage } from './Updater';

interface MajorUpdaterAttrs extends ComponentAttrs {
  coreUpdate: UpdatedPackage;
}

export default class MajorUpdater<T extends MajorUpdaterAttrs = MajorUpdaterAttrs> extends Component<T> {
  view(vnode: Mithril.Vnode<ComponentAttrs, this>): Mithril.Children {
    return (
      <div className="Form-group PackageManager-majorUpdate">
        <img alt="flarum logo" src={app.forum.attribute('baseUrl') + '/assets/extensions/sycho-package-manager/flarum.svg'} />
        <label>{app.translator.trans('sycho-package-manager.admin.major_updater.title', { version: this.attrs.coreUpdate['latest-major'] })}</label>
        <p className="helpText">{app.translator.trans('sycho-package-manager.admin.major_updater.description')}</p>
        <div className="PackageManager-updaterControls">
          <Tooltip text={app.translator.trans('sycho-package-manager.admin.major_updater.dry_run_help')}>
            <Button className="Button" icon="fas fa-vial">
              {app.translator.trans('sycho-package-manager.admin.major_updater.dry_run')}
            </Button>
          </Tooltip>
          <Button className="Button" icon="fas fa-play">
            {app.translator.trans('sycho-package-manager.admin.major_updater.update')}
          </Button>
        </div>
      </div>
    );
  }
}
