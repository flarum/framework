import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';

import Installer from './Installer';
import Updater from './Updater';

export default class ControlSection extends Component {
  view() {
    return (
      <div className="ExtensionPage-permissions PackageManager-controlSection">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('flarum-package-manager.admin.sections.control.title')}</h2>
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
