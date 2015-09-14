import Component from 'flarum/Component';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';

/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 *
 * @abstract
 */
export default class Modal extends Component {
  constructor(...args) {
    super(...args);

    /**
     * An alert component to show below the header.
     *
     * @type {Alert}
     */
    this.alert = null;
  }

  view() {
    if (this.alert) {
      this.alert.props.dismissible = false;
    }

    return (
      <div className={'Modal modal-dialog ' + this.className()}>
        <div className="Modal-content">
          {this.isDismissible() ? (
            <div className="Modal-close App-backControl">
              {Button.component({
                icon: 'times',
                onclick: this.hide.bind(this),
                className: 'Button Button--icon Button--link'
              })}
            </div>
          ) : ''}

          <form onsubmit={this.onsubmit.bind(this)}>
            <div className="Modal-header">
              <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
            </div>

            {alert ? <div className="Modal-alert">{this.alert}</div> : ''}

            {this.content()}
          </form>
        </div>
      </div>
    );
  }

  /**
   * Determine whether or not the modal should be dismissible via an 'x' button.
   *
   * @return {Boolean}
   */
  isDismissible() {
    return true;
  }

  /**
   * Get the class name to apply to the modal.
   *
   * @return {String}
   * @abstract
   */
  className() {
  }

  /**
   * Get the title of the modal dialog.
   *
   * @return {String}
   * @abstract
   */
  title() {
  }

  /**
   * Get the content of the modal.
   *
   * @return {VirtualElement}
   * @abstract
   */
  content() {
  }

  /**
   * Handle the modal form's submit event.
   *
   * @param {Event} e
   */
  onsubmit() {
  }

  /**
   * Focus on the first input when the modal is ready to be used.
   */
  onready() {
    this.$('form :input:first').focus().select();
  }

  /**
   * Hide the modal.
   */
  hide() {
    app.modal.close();
  }

  /**
   * Show an alert describing errors returned from the API, and give focus to
   * the first relevant field.
   *
   * @param {Object} response
   */
  handleErrors(response) {
    const errors = response && response.errors;

    if (errors) {
      this.alert = new Alert({
        type: 'error',
        children: errors.map((error, k) => [error.detail, k < errors.length - 1 ? m('br') : ''])
      });
    }

    m.redraw();

    if (errors) {
      this.$('form [name=' + errors[0].source.pointer.replace('/data/attributes/', '') + ']').select();
    } else {
      this.$('form :input:first').select();
    }
  }
}
