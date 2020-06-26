import Component from '../Component';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  init() {
    this.state = this.props.state;

    this.state.on('show', () => {
      const dismissible = !!(this.state.modalDismissible ? this.state.modalDismissible() : true);
      this.$()
        .modal({
          backdrop: dismissible || 'static',
          keyboard: dismissible,
        })
        .modal('show');
    });

    this.state.on('hide', () => {
      this.$().modal('hide');
    });
  }

  view() {
    const modal = this.state.getModal();

    return <div className="ModalManager modal fade">{modal ? modal.type.component({ ...modal.attrs, state: this.state }) : ''}</div>;
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;

    this.$().on('hidden.bs.modal', this.state.clear.bind(this.state)).on('shown.bs.modal', this.state.onready.bind(this.state));
  }
}
