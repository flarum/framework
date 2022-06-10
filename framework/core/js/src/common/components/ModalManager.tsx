import Component from '../Component';

import { createFocusTrap, FocusTrap } from '../utils/focusTrap';

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
    return this.attrs.state.modalList.map((modal: any) => {
      const Tag = modal?.componentClass;

      // Make stackable modals
      const zIndex = 9999 + modal.key;

      return (
        <div className="ModalManager modal" modal-key={modal.key} style={{ zIndex }}>
          {!!Tag && (
            <Tag
              key={modal?.key}
              {...modal.attrs}
              animateShow={this.animateShow.bind(this)}
              animateHide={this.animateHide.bind(this)}
              state={this.attrs.state}
            />
          )}

          <div className="backdrop" key={`backdrop-${modal.key}`} onclick={this.handleBackdropClick.bind(this)} />
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
        // Get current dialog key
        const dialogKey = this?.attrs?.state?.modal?.key;

        // Deactivate focus trap if there's a new dialog/closed
        if (this.focusTrap && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap!.deactivate?.();
        }

        // Activate focus trap if there's a new dialog which is not trapped yet
        if (this.dialogElement && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap = createFocusTrap(this.dialogElement as HTMLElement, { allowOutsideClick: true });

          this.focusTrap!.activate?.();
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
  private get dialogElement(): HTMLElement {
    return document.body.querySelector(`div[modal-key="${this?.attrs?.state?.modal?.key}"] .Modal`) as HTMLElement;
  }

  /**
   * Get backdrop element of active dialog
   */
  private get backdropElement(): HTMLElement {
    return document.body.querySelector(`div[modal-key="${this?.attrs?.state?.modal?.key}"] .backdrop`) as HTMLElement;
  }

  animateShow(readyCallback: () => void): void {
    if (!this.attrs.state.modal) return;

    this.dialogElement.addEventListener('transitionend', () => readyCallback(), { once: true });

    // Fade in
    requestAnimationFrame(() => {
      this.dialogElement.classList.add('in');
    });
  }

  animateHide(closedCallback: () => void = () => {}): void {
    if (this.modalClosing) return;
    this.modalClosing = true;

    this.backdropElement.addEventListener(
      'transitionend',
      () => {
        this.modalClosing = false;
        m.redraw();

        // Close the dialog
        this.attrs.state.close();

        closedCallback();
      },
      { once: true }
    );

    this.dialogElement.classList.remove('in');
    this.dialogElement.classList.add('out');
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
