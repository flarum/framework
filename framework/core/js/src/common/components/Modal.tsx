import app from '../../common/app';
import Component from '../Component';
import Alert, { AlertAttrs } from './Alert';
import Button from './Button';

import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
import type RequestError from '../utils/RequestError';
import type ModalManager from './ModalManager';
import fireDebugWarning from '../helpers/fireDebugWarning';

export interface IInternalModalAttrs {
  state: ModalManagerState;
  animateShow: ModalManager['animateShow'];
  animateHide: ModalManager['animateHide'];
}

/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 */
export default abstract class Modal<ModalAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Component<ModalAttrs> {
  /**
   * Determine whether or not the modal should be dismissible via an 'x' button.
   */
  static readonly isDismissible: boolean = true;

  protected loading: boolean = false;

  /**
   * Attributes for an alert component to show below the header.
   */
  alertAttrs: AlertAttrs | null = null;

  oninit(vnode: Mithril.Vnode<ModalAttrs, this>) {
    super.oninit(vnode);

    // TODO: [Flarum 2.0] Remove the code below.
    // This code prevents extensions which do not implement all abstract methods of this class from breaking
    // the forum frontend. Without it, function calls would would error rather than returning `undefined.`

    const missingMethods: string[] = [];

    ['className', 'title', 'content', 'onsubmit'].forEach((method) => {
      if (!(this as any)[method]) {
        (this as any)[method] = function (): void {};
        missingMethods.push(method);
      }
    });

    if (missingMethods.length > 0) {
      fireDebugWarning(
        `Modal \`${this.constructor.name}\` does not implement all abstract methods of the Modal super class. Missing methods: ${missingMethods.join(
          ', '
        )}.`
      );
    }
  }

  oncreate(vnode: Mithril.VnodeDOM<ModalAttrs, this>) {
    super.oncreate(vnode);

    this.attrs.animateShow(() => this.onready());
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<ModalAttrs, this>): Promise<void> | void {
    super.onbeforeremove(vnode);

    // If the global modal state currently contains a modal,
    // we've just opened up a new one, and accordingly,
    // we don't need to show a hide animation.
    if (!this.attrs.state.modal) {
      this.attrs.animateHide();
      // Here, we ensure that the animation has time to complete.
      // See https://mithril.js.org/lifecycle-methods.html#onbeforeremove
      // Bootstrap's Modal.TRANSITION_DURATION is 300 ms.
      return new Promise((resolve) => setTimeout(resolve, 300));
    }
  }

  /**
   * @todo split into FormModal and Modal in 2.0
   */
  view() {
    if (this.alertAttrs) {
      this.alertAttrs.dismissible = false;
    }

    return (
      <div className={'Modal modal-dialog ' + this.className()}>
        <div className="Modal-content">
          {(this.constructor as typeof Modal).isDismissible && (
            <div className="Modal-close App-backControl">
              {Button.component({
                icon: 'fas fa-times',
                onclick: () => this.hide(),
                className: 'Button Button--icon Button--link',
                'aria-label': app.translator.trans('core.lib.modal.close'),
              })}
            </div>
          )}

          <form onsubmit={this.onsubmit.bind(this)}>
            <div className="Modal-header">
              <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
            </div>

            {this.alertAttrs ? <div className="Modal-alert">{Alert.component(this.alertAttrs)}</div> : ''}

            {this.content()}
          </form>
        </div>
      </div>
    );
  }

  /**
   * Get the class name to apply to the modal.
   */
  abstract className(): string;

  /**
   * Get the title of the modal dialog.
   */
  abstract title(): Mithril.Children;

  /**
   * Get the content of the modal.
   */
  abstract content(): Mithril.Children;

  /**
   * Handle the modal form's submit event.
   */
  onsubmit(e: SubmitEvent): void {
    // ...
  }

  /**
   * Callback executed when the modal is shown and ready to be interacted with.
   *
   * @remark Focuses the first input in the modal.
   */
  onready(): void {
    this.$().find('input, select, textarea').first().trigger('focus').trigger('select');
  }

  /**
   * Hides the modal.
   */
  hide(): void {
    this.attrs.state.close();
  }

  /**
   * Sets `loading` to false and triggers a redraw.
   */
  loaded(): void {
    this.loading = false;
    m.redraw();
  }

  /**
   * Shows an alert describing an error returned from the API, and gives focus to
   * the first relevant field involved in the error.
   */
  onerror(error: RequestError): void {
    this.alertAttrs = error.alert;

    m.redraw();

    if (error.status === 422 && error.response?.errors) {
      this.$('form [name=' + (error.response.errors as any[])[0].source.pointer.replace('/data/attributes/', '') + ']').trigger('select');
    } else {
      this.onready();
    }
  }
}
