import Modal from "../../common/components/Modal";
import Button from "../../common/components/Button";

/**
 * A basic confirm modal.
 */
export default class ConfirmModal extends Modal {
  /**
   * Initialize the modal.
   * @param {String} title Modal title
   * @param {String} message Message, shown above buttons
   * @param {String} positiveButton Text to show on positive button
   * @param {String} negativeButton Text to show on negative button
   * @param {Function} callback Callback called when 
   */
  init(title, message, positiveButton, negativeButton, callback) {
    super.init();

    this.titleProp = title;
    this.message = message;
    this.positiveButton = positiveButton;
    this.negativeButton = negativeButton;
    this.positiveCallback = callback;
    this.loading = false;
  }

  title() {
    return this.titleProp;
  }

  className() {
    return 'ConfirmModal Modal--small';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p>{this.message}</p>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              children: this.positiveButton,
              type: 'submit',
              loading: this.loading
            })}
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--block',
              children: this.negativeButton,
              onclick: this.hide.bind(this),  
              disabled: this.loading // Disable negative button when loading
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    this.loading = true;
    this.positiveCallback().then((() => {
      this.loading = false;
      this.hide();
    }).bind(this));

    return false;
  }
}
