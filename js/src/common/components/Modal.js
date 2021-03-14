import Component from '../Component';
import Alert from './Alert';
import Button from './Button';

/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 *
 * @abstract
 */
export default class Modal extends Component {
  /**
   * Determine whether or not the modal should be dismissible.
   */
  static isDismissible = true;

  /**
   * Require the user to confirm they wish to close the modal **after clicking
   * on the backdrop or pressing Escape**.
   *
   * It's recommended to keep this enabled for modals which contain forms that
   * may take a user a longer amount of time to complete to prevent frustration.
   *
   * This does not affect closure via the close button.
   */
  static requireImplicitCloseConfirmation = true;

  /**
   * Require the user to confirm they wish to close the modal **after clicking
   * the top close button, or when calling `hide()`**.
   *
   * This does not affect closure via an Esc key press, or a click on the
   * backdrop.
   */
  static requireExplicitCloseConfirmation = false;

  /**
   * Attributes for an alert component to show below the header.
   *
   * @type {object}
   */
  alertAttrs = null;

  oncreate(vnode) {
    super.oncreate(vnode);

    this.attrs.animateShow(() => this.onready());
  }

  onbeforeremove() {
    // If the global modal state currently contains a modal,
    // we've just opened up a new one, and accordingly,
    // we don't need to show a hide animation.
    if (!this.attrs.state.modal) {
      this.attrs.animateHide();
      // Here, we ensure that the animation has time to complete.
      // See https://mithril.js.org/lifecycle-methods.html#onbeforeremove
      // Bootstrap's Modal.TRANSITION_DURATION is 300 ms.
      return new Promise((resolve) => setTimeout(resolve, 300));
    }
  }

  view() {
    if (this.alertAttrs) {
      this.alertAttrs.dismissible = false;
    }

    return (
      <div className={'Modal modal-dialog ' + this.className()}>
        <div className="Modal-content">
          {this.constructor.isDismissible && (
            <div className="Modal-close App-backControl">
              <Button icon="fas fa-times" onclick={() => this.hide.bind(this)()} className="Button Button--icon Button--link" />
            </div>
          )}

          <form onsubmit={this.onsubmit.bind(this)}>
            <div className="Modal-header">
              <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
            </div>

            {this.alertAttrs ? <div className="Modal-alert">{Alert.component(this.alertAttrs)}</div> : ''}

            {this.content()}
          </form>
        </div>
      </div>
    );
  }

  /**
   * Get the class name to apply to the modal.
   *
   * @return {string}
   * @abstract
   */
  className() {}

  /**
   * Get the title of the modal dialog.
   *
   * @return {string}
   * @abstract
   */
  title() {}

  /**
   * Get the content of the modal.
   *
   * @return {VirtualElement}
   * @abstract
   */
  content() {}

  /**
   * Handle the modal form's submit event.
   *
   * @param {SubmitEvent} e
   */
  onsubmit() {}

  /**
   * Focus on the first input when the modal is ready to be used.
   */
  onready() {
    this.$('form').find('input, select, textarea').first().focus().select();
  }

  /**
   * Hide the modal.
   *
   * @param {boolean} [noConfirm=false] Whether to skip the user confirmation check before hiding the modal. No effect if `requireExplicitCloseConfirmation` is `false`.
   */
  hide(noConfirm = false) {
    // `requireExplicitCloseConfirmation` is static, so we need to access it like this
    // See: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Classes/static#calling_static_members_from_a_class_constructor_and_other_methods
    if (noConfirm || !this.constructor.requireExplicitCloseConfirmation) {
      this.attrs.state.close();
    } else {
      // We need the user to confirm the modal closure before we actually do it
      this.attrs.closeWithConfirmation(null, true);
    }
  }

  /**
   * Stop loading.
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }

  /**
   * Show an alert describing an error returned from the API, and give focus to
   * the first relevant field.
   *
   * @param {RequestError} error
   */
  onerror(error) {
    this.alertAttrs = error.alert;

    m.redraw();

    if (error.status === 422 && error.response.errors) {
      this.$('form [name=' + error.response.errors[0].source.pointer.replace('/data/attributes/', '') + ']').select();
    } else {
      this.onready();
    }
  }
}
