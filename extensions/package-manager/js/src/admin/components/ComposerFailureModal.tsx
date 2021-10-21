import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import { ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import Mithril from 'mithril';

interface Attrs extends ComponentAttrs {
  output: string;
}

export default class ComposerFailureModal<T extends Attrs = Attrs> extends Modal<T> {
  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    if (this.attrs.error.guessed_cause) {
      this.alertAttrs = {
        type: 'error',
        content: app.translator.trans(`flarum-package-manager.admin.failure_modal.guessed_cause.${this.attrs.error.guessed_cause}`),
      };
    }
  }

  className() {
    return 'Modal--large ComposerFailureModal';
  }

  title() {
    return app.translator.trans('flarum-package-manager.admin.failure_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <details>
          <summary>{app.translator.trans('flarum-package-manager.admin.failure_modal.show_composer_output')}</summary>
          <pre className="ComposerFailureModal-output">{this.attrs.error.output}</pre>
        </details>
      </div>
    );
  }
}
