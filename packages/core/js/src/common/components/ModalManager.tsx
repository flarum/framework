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

  view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children {
    const modal = this.attrs.state.modal;
    const Tag = modal?.componentClass;

    return (
      <div className="ModalManager modal fade">
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

    // Ensure the modal state is notified about a closed modal, even when the
    // DOM-based Bootstrap JavaScript code triggered the closing of the modal,
    // e.g. via ESC key or a click on the modal backdrop.
    this.$().on('hidden.bs.modal', this.attrs.state.close.bind(this.attrs.state));

    this.focusTrap = createFocusTrap(this.element as HTMLElement, { allowOutsideClick: true });
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

  animateShow(readyCallback: () => void): void {
    if (!this.attrs.state.modal) return;

    const dismissible = !!this.attrs.state.modal.componentClass.isDismissible;

    this.modalShown = true;

    // If we are opening this modal while another modal is already open,
    // the shown event will not run, because the modal is already open.
    // So, we need to manually trigger the readyCallback.
    if (this.$().hasClass('in')) {
      readyCallback();
      return;
    }

    this.$()
      .one('shown.bs.modal', readyCallback)
      // @ts-expect-error: No typings available for Bootstrap modals.
      .modal({
        backdrop: dismissible || 'static',
        keyboard: dismissible,
      })
      .modal('show');
  }

  animateHide(): void {
    // @ts-expect-error: No typings available for Bootstrap modals.
    this.$().modal('hide');

    this.modalShown = false;
  }
}
