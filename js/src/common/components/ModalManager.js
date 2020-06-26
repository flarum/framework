import Component from '../Component';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  init() {
    this.state = this.props.state;
  }

  view() {
    const modal = this.state.modal;

    return (
      <div className="ModalManager modal fade">
        {modal ? modal.type.component({ ...modal.attrs, onshow: this.animateShow.bind(this), onhide: this.animateHide.bind(this) }) : ''}
      </div>
    );
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;

    // Ensure the modal state is notified about a closed modal, even when the
    // DOM-based Bootstrap JavaScript code triggered the closing of the modal,
    // e.g. via ESC key or a click on the modal backdrop.
    this.$().on('hidden.bs.modal', this.state.close.bind(this.state));
  }

  animateShow(readyCallback) {
    const dismissible = !!this.state.modal.type.isDismissible();

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
