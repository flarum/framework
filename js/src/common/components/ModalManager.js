import Component from '../Component';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  view() {
    const modal = this.attrs.state.modal;

    return (
      <div className="ModalManager modal fade">
        {modal
          ? modal.componentClass.component({
              ...modal.attrs,
              animateShow: this.animateShow.bind(this),
              animateHide: this.animateHide.bind(this),
              state: this.attrs.state,
            })
          : ''}
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Ensure the modal state is notified about a closed modal, even when the
    // DOM-based Bootstrap JavaScript code triggered the closing of the modal,
    // e.g. via ESC key or a click on the modal backdrop.
    this.$().on('hidden.bs.modal', this.attrs.state.close.bind(this.attrs.state));
  }

  animateShow(readyCallback) {
    const isDismissible = !!this.attrs.state.modal.componentClass.isDismissible;

    // If the modal isn't dismissible, set these options to false.
    const backdropDismissible = isDismissible && !!this.attrs.state.modal.componentClass.dismissOnBackdropClick;
    const keyboardDismissible = isDismissible && !!this.attrs.state.modal.componentClass.dismissOnEscapeKeyPress;

    // If we are opening this modal while another modal is already open,
    // the shown event will not run, because the modal is already open.
    // So, we need to manually trigger the readyCallback.
    if (this.$().hasClass('in')) {
      readyCallback();
      return;
    }

    this.$()
      .one('shown.bs.modal', readyCallback)
      .modal({
        // 'static' means that a backdrop click doesn't dismiss the modal
        backdrop: backdropDismissible || 'static',
        keyboard: keyboardDismissible,
      })
      .modal('show');

    // Disabling backdrop dismissal also disables keyboard dismissibility
    // but we want to be able to provide key-based dismissal while having
    // backdrop-based disabled. To do this, we set up our own event
    // handler to hide the modal if we press Escape.
    if (!backdropDismissible && keyboardDismissible) {
      this.$().on('keypress', function (e) {
        if (e.key === 'Escape') {
          this.animateHide();
        }
      });
    }
  }

  animateHide() {
    this.$().modal('hide');
  }
}
