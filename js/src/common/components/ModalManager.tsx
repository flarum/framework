import Component from '../Component';

import { createFocusTrap, FocusTrap } from '../utils/focusTrap';

import type ModalManagerState from '../states/ModalManagerState';
import type Mithril from 'mithril';

interface IModalManagerAttrs {
  state: ModalManagerState;
}

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
  protected focusTrap: FocusTrap | undefined;

  /**
   * Whether a modal is currently shown by this modal manager.
   */
  protected modalShown: boolean = false;

  protected modalClosing: boolean = false;

  protected clickStartedOnBackdrop: boolean = false;

  view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children {
    const modal = this.attrs.state.modal;
    const Tag = modal?.componentClass;

    return (
      <div className="ModalManager modal">
        {!!Tag && (
          <Tag
            key={modal?.key}
            {...modal.attrs}
            animateShow={this.animateShow.bind(this)}
            animateHide={this.animateHide.bind(this)}
            state={this.attrs.state}
          />
        )}
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.oncreate(vnode);

    this.focusTrap = createFocusTrap(this.element as HTMLElement);
  }

  onupdate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.onupdate(vnode);

    requestAnimationFrame(() => {
      try {
        if (this.modalShown) this.focusTrap!.activate?.();
        else this.focusTrap!.deactivate?.();
      } catch {
        // We can expect errors to occur here due to the nature of mithril rendering
      }
    });
  }

  private get dialogElement(): HTMLDialogElement {
    return this.element.querySelector('dialog') as HTMLDialogElement;
  }

  animateShow(readyCallback: () => void): void {
    if (!this.attrs.state.modal) return;

    const dismissibleState = this.attrs.state.modal.componentClass.dismissibleOptions;

    this.modalShown = true;

    // Register with polyfill
    dialogPolyfill.registerDialog(this.dialogElement);

    if (!dismissibleState.viaEscKey) this.dialogElement.addEventListener('cancel', this.preventEscPressHandler);
    if (dismissibleState.viaBackdropClick) {
      this.dialogElement.addEventListener('mousedown', (e) => this.handleBackdropMouseDown.call(this, e));
      this.dialogElement.addEventListener('click', (e) => this.handleBackdropClick.call(this, e));
    }

    this.dialogElement.addEventListener('transitionend', () => readyCallback(), { once: true });
    // Ensure the modal state is ALWAYS notified about a closed modal
    this.dialogElement.addEventListener('close', this.attrs.state.close.bind(this.attrs.state));

    // Use close animation instead
    this.dialogElement.addEventListener('cancel', this.animateCloseHandler.bind(this));

    this.dialogElement.showModal();

    // Fade in
    requestAnimationFrame(() => {
      this.dialogElement.classList.add('in');
    });
  }

  animateHide(): void {
    if (this.modalClosing || !this.modalShown) return;
    this.modalClosing = true;

    this.dialogElement.addEventListener(
      'transitionend',
      () => {
        this.dialogElement.close();
        this.dialogElement.removeEventListener('cancel', this.preventEscPressHandler);

        this.modalShown = false;
        this.modalClosing = false;
        m.redraw();
      },
      { once: true }
    );

    this.dialogElement.classList.remove('in');
    this.dialogElement.classList.add('out');
  }

  protected animateCloseHandler(this: this, e: Event) {
    e.preventDefault();

    this.animateHide();
  }

  protected preventEscPressHandler(this: this, e: Event) {
    e.preventDefault();
  }

  protected handleBackdropMouseDown(this: this, e: MouseEvent) {
    // If it's a mousedown on the dialog element, the backdrop has been clicked.
    // If it was a mousedown in the modal, the element would be `div.Modal-content` or some other element.
    if (e.target !== this.dialogElement) return;

    this.clickStartedOnBackdrop = true;
    window.addEventListener(
      'mouseup',
      () => {
        if (e.target !== this.dialogElement) this.clickStartedOnBackdrop = false;
      },
      { once: true }
    );
  }

  protected handleBackdropClick(this: this, e: MouseEvent) {
    // If it's a click on the dialog element, the backdrop has been clicked.
    // If it was a click in the modal, the element would be `div.Modal-content` or some other element.
    if (e.target !== this.dialogElement || !this.clickStartedOnBackdrop) return;

    this.clickStartedOnBackdrop = false;
    this.animateHide();
  }
}
