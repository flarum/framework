import app from '../../common/app';
import Component from '../Component';
import Alert, { AlertAttrs } from './Alert';
import Button from './Button';

import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
import type ModalManager from './ModalManager';
import fireDebugWarning from '../helpers/fireDebugWarning';
import classList from '../utils/classList';

export interface IInternalModalAttrs {
  state: ModalManagerState;
  animateShow: ModalManager['animateShow'];
  animateHide: ModalManager['animateHide'];
}

export interface IDismissibleOptions {
  viaCloseButton: boolean;
  viaEscKey: boolean;
  viaBackdropClick: boolean;
}

/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 */
export default abstract class Modal<ModalAttrs extends IInternalModalAttrs = IInternalModalAttrs, CustomState = undefined> extends Component<
  ModalAttrs,
  CustomState
> {
  /**
   * Can the model be dismissed with a close button (X)?
   *
   * If `false`, no close button is shown.
   */
  protected static readonly isDismissibleViaCloseButton: boolean = true;
  /**
   * Can the modal be dismissed by pressing the Esc key on a keyboard?
   */
  protected static readonly isDismissibleViaEscKey: boolean = true;
  /**
   * Can the modal be dismissed via a click on the backdrop.
   */
  protected static readonly isDismissibleViaBackdropClick: boolean = true;

  static get dismissibleOptions(): IDismissibleOptions {
    return {
      viaCloseButton: this.isDismissibleViaCloseButton,
      viaEscKey: this.isDismissibleViaEscKey,
      viaBackdropClick: this.isDismissibleViaBackdropClick,
    };
  }

  protected loading: boolean = false;

  /**
   * Attributes for an alert component to show below the header.
   */
  alertAttrs: AlertAttrs | null = null;

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
      <div className={classList('Modal modal-dialog fade', this.className())}>
        <div className="Modal-content">
          {this.dismissibleOptions.viaCloseButton && (
            <div className="Modal-close App-backControl">
              <Button
                icon="fas fa-times"
                onclick={() => this.hide()}
                className="Button Button--icon Button--link"
                aria-label={app.translator.trans('core.lib.modal.close')}
              />
            </div>
          )}
          {this.wrapper(this.inner())}
        </div>
      </div>
    );
  }

  protected wrapper(children: Mithril.Children): Mithril.Children {
    return <>{children}</>;
  }

  protected inner(): Mithril.Children {
    return (
      <>
        <div className="Modal-header">
          <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
        </div>

        {!!this.alertAttrs && (
          <div className="Modal-alert">
            <Alert {...this.alertAttrs} />
          </div>
        )}

        {this.content()}
      </>
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
   * Callback executed when the modal is shown and ready to be interacted with.
   */
  onready(): void {
    // ...
  }

  /**
   * Hides the modal.
   */
  hide(): void {
    this.attrs.animateHide();
  }

  /**
   * Sets `loading` to false and triggers a redraw.
   */
  loaded(): void {
    this.loading = false;
    m.redraw();
  }

  protected get dismissibleOptions(): IDismissibleOptions {
    return (this.constructor as typeof Modal).dismissibleOptions;
  }
}
