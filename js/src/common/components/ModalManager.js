import Component from '../Component';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  view(vnode) {
    const modal = this.attrs.state.modal;

    return (
      <div className="ModalManager modal fade">
        {modal ? modal.componentClass.component({ ...modal.attrs, onshow: this.animateShow.bind(this), onhide: this.animateHide.bind(this) }) : ''}
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
    const dismissible = !!this.attrs.state.modal.componentClass.isDismissible;

    this.$()
      .one('shown.bs.modal', readyCallback)
      .modal({
        backdrop: dismissible || 'static',
        keyboard: dismissible,
      })
      .modal('show');
  }

  animateHide() {
    this.$().modal('hide');
  }
}
