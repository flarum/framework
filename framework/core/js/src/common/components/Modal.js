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
   * Determine whether or not the modal should be dismissible via an 'x' button.
   */
  static isDismissible = true;

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

  onbeforeremove(vnode) {
    super.onbeforeremove(vnode);

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
          {this.constructor.isDismissible ? (
            <div className="Modal-close App-backControl">
              {Button.component({
                icon: 'fas fa-times',
                onclick: this.hide.bind(this),
                className: 'Button Button--icon Button--link',
              })}
            </div>
          ) : (
            ''
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
   * @return {String}
   * @abstract
   */
  className() {}

  /**
   * Get the title of the modal dialog.
   *
   * @return {String}
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
   * @param {Event} e
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
   */
  hide() {
    this.attrs.state.close();
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
