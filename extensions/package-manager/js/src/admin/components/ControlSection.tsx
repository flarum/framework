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
      <div className="ExtensionPage-permissions ExtensionManager-controlSection">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('flarum-extension-manager.admin.sections.control.title')}</h2>
          </div>
        </div>
        <div className="container">
          {app.data['flarum-extension-manager.writable_dirs'] ? (
            <>
              <Installer />
              <Updater />
            </>
          ) : (
            <div className="Form-group">
              <Alert type="warning" dismissible={false}>
                {app.translator.trans('flarum-extension-manager.admin.file_permissions')}
              </Alert>
            </div>
          )}
        </div>
      </div>
    );
  }
}
