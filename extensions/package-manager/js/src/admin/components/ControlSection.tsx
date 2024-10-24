import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import { ComponentAttrs } from 'flarum/common/Component';

import Installer from './Installer';
import Updater from './Updater';
import Mithril from 'mithril';
import Form from 'flarum/common/components/Form';

export default class ControlSection extends Component<ComponentAttrs> {
  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    return (
      <div className="ExtensionPage-settings ExtensionManager-controlSection">
        <div className="container">
          {app.data['flarum-extension-manager.writable_dirs'] ? (
            <Form>
              <Installer />
              <Updater />
            </Form>
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
