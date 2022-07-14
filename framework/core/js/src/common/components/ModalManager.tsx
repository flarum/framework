import Component from '../Component';

import { createFocusTrap, FocusTrap } from '../utils/focusTrap';

import { disableBodyScroll, clearAllBodyScrollLocks } from 'body-scroll-lock';

import type ModalManagerState from '../states/ModalManagerState';
import type Mithril from 'mithril';

interface IModalManagerAttrs {
  state: ModalManagerState;
}

/**
 * The `ModalManager` component manages one or more modal dialogs. Stacking modals
 * is supported. Multiple dialogs can be shown at once; loading a new component
 * into the ModalManager will overwrite the previous one.
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
  // Current focus trap
  protected focusTrap: FocusTrap | undefined;

  // Keep track of the last set focus trap
  protected lastSetFocusTrap: number | undefined;

  // Keep track if there's an modal closing
  protected modalClosing: boolean = false;

  view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children {
    return this.attrs.state.modalList.map((modal, i) => {
      const Tag = modal?.componentClass;

      return (
        <div
          key={modal.key}
          class="ModalManager modal"
          data-modal-key={modal.key}
          data-modal-number={i}
          role="dialog"
          aria-modal="true"
          style={{ '--modal-number': i }}
          data-visibility-state={modal.animationState}
          aria-hidden={this.attrs.state.modal !== modal && 'true'}
        >
          {!!Tag && (
            <Tag
              key={modal.key}
              {...modal.attrs}
              animateShow={this.animateShow.bind(this)}
              animateHide={this.animateHide.bind(this)}
              state={this.attrs.state}
            />
          )}

          <div class="backdrop" key={`backdrop-${modal.key}`} onclick={this.handleBackdropClick.bind(this)} />
        </div>
      );
    });
  }

  oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.oncreate(vnode);

    // Register keyup
    document.body.addEventListener('keyup', this.handleEscPress.bind(this));
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    document.body.removeEventListener('keyup', this.handleEscPress);
  }

  onupdate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.onupdate(vnode);

    requestAnimationFrame(() => {
      try {
        // Main content should gain or lose `aria-hidden` when modals are shown/removed
        // See: http://web-accessibility.carnegiemuseums.org/code/dialogs/

        if (!this.attrs.state.isModalOpen()) {
          document.getElementById('app')?.setAttribute('aria-hidden', 'false');
          this.focusTrap!.deactivate?.();
          clearAllBodyScrollLocks();

          return;
        }

        document.getElementById('app')?.setAttribute('aria-hidden', 'true');

        // Get current dialog key
        const dialogKey = this.attrs.state.modal!.key;

        // Deactivate focus trap if there's a new dialog/closed
        if (this.focusTrap && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap!.deactivate?.();

          clearAllBodyScrollLocks();
        }

        // Activate focus trap if there's a new dialog which is not trapped yet
        if (this.activeDialogElement && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap = createFocusTrap(this.activeDialogElement as HTMLElement, { allowOutsideClick: true });
          this.focusTrap!.activate?.();

          disableBodyScroll(this.activeDialogElement, { reserveScrollBarGap: true });
        }

        // Update key of current opened modal
        this.lastSetFocusTrap = dialogKey;
      } catch {
        // We can expect errors to occur here due to the nature of mithril rendering
      }
    });
  }

  /**
   * Get current active dialog
   */
  private get activeDialogElement(): HTMLElement {
    return document.body.querySelector(`div[data-modal-key="${this?.attrs?.state?.modal?.key}"] .Modal`) as HTMLElement;
  }

  /**
   * Get backdrop element of active dialog
   */
  private get activeBackdropElement(): HTMLElement {
    return document.body.querySelector(`div[data-modal-key="${this?.attrs?.state?.modal?.key}"] .backdrop`) as HTMLElement;
  }

  animateShow(readyCallback: () => void = () => {}): void {
    if (!this.attrs.state.modal) return;

    this.activeDialogElement.addEventListener(
      'transitionend',
      () => {
        this.attrs.state.modal!.animationState = 'entered';
        m.redraw();

        readyCallback();
      },
      { once: true }
    );

    requestAnimationFrame(() => {
      this.activeDialogElement.classList.add('in');
    });
  }

  animateHide(closedCallback: () => void = () => {}): void {
    if (this.modalClosing) return;
    this.modalClosing = true;

    const afterModalClosedCallback = () => {
      this.modalClosing = false;

      // Close the dialog
      this.attrs.state.close();

      closedCallback();
    };

    this.activeDialogElement.addEventListener('transitionend', afterModalClosedCallback, { once: true });

    this.attrs.state.modal!.animationState = 'exiting';

    // Update animation state of next modal on the stack
    const thisModalIndex = parseInt(this.activeDialogElement.parentElement?.getAttribute('data-modal-number')!);
    thisModalIndex >= 1 && (this.attrs.state.modalList[thisModalIndex - 1].animationState = 'entered');

    this.activeDialogElement.classList.remove('in');
    this.activeDialogElement.classList.add('out');
  }

  protected handleEscPress(e: KeyboardEvent): any {
    if (!this.attrs.state.modal) return;

    const dismissibleState = this.attrs.state.modal.componentClass.dismissibleOptions;

    // Close the dialog if the escape key was pressed
    // Check if closing via escape key is enabled
    if (e.key === 'Escape' && dismissibleState.viaEscKey) {
      e.preventDefault();

      this.animateHide();
    }
  }

  protected handleBackdropClick(this: this): any {
    if (!this.attrs.state.modal) return;

    const dismissibleState = this.attrs.state.modal.componentClass.dismissibleOptions;

    // Close the dialog if the backdrop was clicked
    // Check if closing via backdrop click is enabled
    if (dismissibleState.viaBackdropClick) {
      this.animateHide();
    }
  }
}
