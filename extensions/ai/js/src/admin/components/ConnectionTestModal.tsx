import app from 'flarum/admin/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import ConnectionTest, { ConnectionTestState } from './ConnectionTest';

export interface ConnectionTestModalAttrs extends IInternalModalAttrs {
  /** A saved connection, or one set in config.php. */
  name: string;
}

/**
 * Tests a saved connection from the list on the Connections tab.
 */
export default class ConnectionTestModal<CustomAttrs extends ConnectionTestModalAttrs = ConnectionTestModalAttrs> extends Modal<CustomAttrs> {
  test = new ConnectionTestState();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.test.start(this.attrs.name);
  }

  className() {
    return 'AiConnectionTestModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-ai.admin.test.title', { name: this.attrs.name });
  }

  content() {
    return (
      <div className="Modal-body">
        <ConnectionTest state={this.test} />
        <div className="Form-group Form-controls">
          <Button className="Button" icon="fas fa-rotate" disabled={this.test.status === 'running'} onclick={() => this.test.start(this.attrs.name)}>
            {app.translator.trans('flarum-ai.admin.test.again_button')}
          </Button>
        </div>
      </div>
    );
  }
}
