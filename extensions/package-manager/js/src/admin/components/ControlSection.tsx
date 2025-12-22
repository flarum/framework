import Component from 'flarum/common/Component';
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
          <Form>
            <Installer />
            <Updater />
          </Form>
        </div>
      </div>
    );
  }
}
