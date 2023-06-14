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

  protected keyUpListener: null | ((e: KeyboardEvent) => void) = null;

  view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children {
    return (
      <>
        {this.attrs.state.modalList.map((modal, i) => {
          const Tag = modal?.componentClass;

          return (
            <div
              key={modal.key}
              className="ModalManager modal"
              data-modal-key={modal.key}
              data-modal-number={i}
              role="dialog"
              aria-modal="true"
              style={{ '--modal-number': i }}
              aria-hidden={this.attrs.state.modal !== modal && 'true'}
            >
              {!!Tag && [
                <Tag
                  key={modal.key}
                  {...modal.attrs}
                  animateShow={this.animateShow.bind(this)}
                  animateHide={this.animateHide.bind(this)}
                  state={this.attrs.state}
                />,
                /* This backdrop is invisible and used for outside clicks to close the modal. */
                <div key={modal.key} className="ModalManager-invisibleBackdrop" onclick={this.handlePossibleBackdropClick.bind(this)} />,
              ]}
            </div>
          );
        })}

        {this.attrs.state.backdropShown && (
          <div
            className="Modal-backdrop backdrop"
            ontransitionend={this.onBackdropTransitionEnd.bind(this)}
            data-showing={!!this.attrs.state.modalList.length}
            style={{ '--modal-count': this.attrs.state.modalList.length }}
          />
        )}
      </>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.oncreate(vnode);

    this.keyUpListener = this.handleEscPress.bind(this);
    document.body.addEventListener('keyup', this.keyUpListener);
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.onbeforeremove(vnode);

    this.keyUpListener && document.body.removeEventListener('keyup', this.keyUpListener);
    this.keyUpListener = null;
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

          disableBodyScroll(this.activeDialogManagerElement!, { reserveScrollBarGap: true });
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
    return document.body.querySelector(`.ModalManager[data-modal-key="${this.attrs.state.modal?.key}"] .Modal`) as HTMLElement;
  }

  /**
   * Get current active dialog
   */
  private get activeDialogManagerElement(): HTMLElement {
    return document.body.querySelector(`.ModalManager[data-modal-key="${this.attrs.state.modal?.key}"]`) as HTMLElement;
  }

  animateShow(readyCallback: () => void = () => {}): void {
    if (!this.attrs.state.modal) return;

    this.activeDialogElement.addEventListener(
      'transitionend',
      () => {
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

    this.activeDialogElement.classList.remove('in');
    this.activeDialogElement.classList.add('out');
  }

  protected handleEscPress(e: KeyboardEvent): void {
    if (!this.attrs.state.modal) return;

    const dismissibleState = this.attrs.state.modal.componentClass.dismissibleOptions;

    // Close the dialog if the escape key was pressed
    // Check if closing via escape key is enabled
    if (e.key === 'Escape' && dismissibleState.viaEscKey) {
      e.preventDefault();

      this.animateHide();
    }
  }

  protected handlePossibleBackdropClick(e: MouseEvent): void {
    if (!this.attrs.state.modal || !this.attrs.state.modal.componentClass.dismissibleOptions.viaBackdropClick) return;

    this.animateHide();
  }

  protected onBackdropTransitionEnd(e: TransitionEvent) {
    if (e.propertyName === 'opacity') {
      const backdrop = e.currentTarget as HTMLDivElement;

      if (backdrop.getAttribute('data-showing') === null) {
        // Backdrop is fading out
        this.attrs.state.backdropShown = false;
        m.redraw();
      }
    }
  }
}
