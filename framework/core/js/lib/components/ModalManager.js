import Component from 'flarum/Component';
import Modal from 'flarum/components/Modal';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  view() {
    return (
      <div className="modal">
        {this.component && this.component.render()}
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    this.$()
      .on('hidden.bs.modal', this.clear.bind(this))
      .on('shown.bs.modal', this.onready.bind(this));
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

    this.component = component;

    m.redraw(true);

    this.$().modal('show');
    this.onready();
  }

  /**
   * Close the modal dialog.
   *
   * @public
   */
  close() {
    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.hideTimeout = setTimeout(() => this.$().modal('hide'));
  }

  /**
   * Clear content from the modal area.
   *
   * @protected
   */
  clear() {
    this.component = null;

    m.redraw();
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
