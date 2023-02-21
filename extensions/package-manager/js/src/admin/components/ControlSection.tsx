import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import { ComponentAttrs } from 'flarum/common/Component';

import Installer from './Installer';
import Updater from './Updater';
import Mithril from 'mithril';

export default class ControlSection extends Component<ComponentAttrs> {
  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    return (
      <div className="ExtensionPage-permissions PackageManager-controlSection">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h1 className="ExtensionTitle">{app.translator.trans('flarum-package-manager.admin.sections.control.title')}</h1>
          </div>
        </div>
        <div className="container">
          {app.data['flarum-package-manager.writable_dirs'] ? (
            <>
              <Installer />
              <Updater />
            </>
          ) : (
            <div className="Form-group">
              <Alert type="warning" dismissible={false}>
                {app.translator.trans('flarum-package-manager.admin.file_permissions')}
              </Alert>
            </div>
          )}
        </div>
      </div>
    );
  }
}
