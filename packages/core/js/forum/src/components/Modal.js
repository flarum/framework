import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Alert from 'flarum/components/Alert';
import icon from 'flarum/helpers/icon';

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

    /**
     * Whether or not the form is processing.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  view() {
    if (this.alert) {
      this.alert.props.dismissible = false;
    }

    return (
      <div className={'modal-dialog ' + this.className()}>
        <div className="modal-content">
          <div className="close back-control">
            <a href="javascript:;" className="btn btn-icon btn-link" onclick={this.hide.bind(this)}>
              {icon('times', {className: 'icon'})}
            </a>
          </div>

          <form onsubmit={this.onsubmit.bind(this)}>
            <div className="modal-header">
              <h3 className="title-control">{this.title()}</h3>
            </div>

            {alert ? <div className="modal-alert">{this.alert}</div> : ''}

            {this.content()}
          </form>
        </div>

        {LoadingIndicator.component({
          className: 'modal-loading' + (this.loading ? ' active' : '')
        })}
      </div>
    );
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
    this.$(':input:first').select();
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
   * @param {Array} errors
   */
  handleErrors(errors) {
    if (errors) {
      this.alert(new Alert({
        type: 'warning',
        message: errors.map((error, k) => [error.detail, k < errors.length - 1 ? m('br') : ''])
      }));
    }

    m.redraw();

    if (errors) {
      this.$('[name=' + errors[0].path + ']').select();
    } else {
      this.$(':input:first').select();
    }
  }
}
