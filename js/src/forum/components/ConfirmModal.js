import Modal from "../../common/components/Modal";
import Button from "../../common/components/Button";

/**
 * A basic confirm modal.
 * The special props are:
 * * `translation` Translation path for some of the elements. It'll be used like this:
 *   `${translation}.title`
 * * `save` Callback called when pressing positive button.
 */
export default class ConfirmModal extends Modal {
  constructor(props, children) {
    super(props, children);

    this.loading = false;
  }

  title() {
    return app.translator.trans(`${this.props.translation}.title`);
  }

  className() {
    return 'ConfirmModal Modal--small';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p>{app.translator.trans(`${this.props.translation}.message`)}</p>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              children: app.translator.trans(`${this.props.translation}.positive`),
              type: 'submit',
              loading: this.loading
            })}
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--block',
              children: app.translator.trans(`${this.props.translation}.negative`),
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
    this.props.save().then((() => {
      this.loading = false;
      this.hide();
    }).bind(this));

    return false;
  }
}
