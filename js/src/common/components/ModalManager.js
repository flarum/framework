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
    return (
      <div className="ModalManager modal fade">{this.state.getModal() ? this.state.getModal().getClass().component({ state: this.state }) : ''}</div>
    );
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;

    this.$().on('hidden.bs.modal', this.state.clear.bind(this.state)).on('shown.bs.modal', this.state.onready.bind(this.state));
  }

  /**
   * Show a modal dialog.
   *
   * @param {Modal} component
   * @public
   */
  show(component) {
    if (!(component instanceof Modal)) {
      throw new Error('The ModalManager component can only show Modal components');
    }

    clearTimeout(this.hideTimeout);

    this.showing = true;
    this.component = component;

    m.redraw(true);

    const dismissible = !!this.component.isDismissible();
    this.$()
      .modal({
        backdrop: dismissible || 'static',
        keyboard: dismissible,
      })
      .modal('show');
  }

  /**
   * Close the modal dialog.
   *
   * @public
   */
  close() {
    if (!this.showing) return;

    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.hideTimeout = setTimeout(() => {
      this.$().modal('hide');
      this.showing = false;
    });
  }

  /**
   * Clear content from the modal area.
   *
   * @protected
   */
  clear() {
    if (this.component) {
      this.component.onhide();
    }

    this.component = null;

    app.current.retain = false;

    m.lazyRedraw();
  }

  /**
   * When the modal dialog is ready to be used, tell it!
   *
   * @protected
   */
  onready() {
    if (this.component && this.component.onready) {
      this.component.onready(this.$());
    }
  }
}
