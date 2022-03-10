import type Component from '../Component';
import Modal from '../components/Modal';

/**
 * Ideally, `show` would take a higher-kinded generic, ala:
 *  `show<Attrs, C>(componentClass: C<Attrs>, attrs: Attrs): void`
 * Unfortunately, TypeScript does not support this:
 * https://github.com/Microsoft/TypeScript/issues/1213
 * Therefore, we have to use this ugly, messy workaround.
 */
type UnsafeModalClass = ComponentClass<any, Modal> & { isDismissible: boolean; component: typeof Component.component };

/**
 * Class used to manage modal state.
 *
 * Accessible on the `app` object via `app.modal` property.
 */
export default class ModalManagerState {
  /**
   * @internal
   */
  modal: null | {
    componentClass: UnsafeModalClass;
    attrs?: Record<string, unknown>;
    key: number;
  } = null;

  /**
   * Used to force re-initialization of modals if a modal
   * is replaced by another of the same type.
   */
  private key = 0;

  private closeTimeout?: NodeJS.Timeout;

  /**
   * Shows a modal dialog.
   *
   * If a modal is already open, the existing one will close and the new modal will replace it.
   *
   * @example <caption>Show a modal</caption>
   * app.modal.show(MyCoolModal, { attr: 'value' });
   *
   * @example <caption>Show a modal from a lifecycle method (`oncreate`, `view`, etc.)</caption>
   * // This "hack" is needed due to quirks with nested redraws in Mithril.
   * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
   */
  show(componentClass: UnsafeModalClass, attrs: Record<string, unknown> = {}): void {
    if (!(componentClass.prototype instanceof Modal)) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      const invalidModalWarning = 'The ModalManager can only show Modals.';
      console.error(invalidModalWarning);
      throw new Error(invalidModalWarning);
    }

    if (this.closeTimeout) clearTimeout(this.closeTimeout);

    this.modal = { componentClass, attrs, key: this.key++ };

    m.redraw.sync();
  }

  /**
   * Closes the currently open dialog, if one is open.
   */
  close(): void {
    if (!this.modal) return;

    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.closeTimeout = setTimeout(() => {
      this.modal = null;
      m.redraw();
    });
  }

  /**
   * Checks if a modal is currently open.
   *
   * @return `true` if a modal dialog is currently open, otherwise `false`.
   */
  isModalOpen(): boolean {
    return !!this.modal;
  }
}
