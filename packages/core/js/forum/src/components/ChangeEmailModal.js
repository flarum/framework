import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
export default class ChangeEmailModal extends Modal {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the email has been changed successfully.
     *
     * @type {Boolean}
     */
    this.success = false;

    /**
     * The value of the email input.
     *
     * @type {function}
     */
    this.email = m.prop(app.session.user.email());
  }

  className() {
    return 'ChangeEmailModal Modal--small';
  }

  title() {
    return app.trans('core.forum.change_email_title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.trans('core.forum.change_email_confirmation_message', {email: <strong>{this.email()}</strong>})}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.trans('core.forum.change_email_dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input type="email" name="email" className="FormControl"
              placeholder={app.session.user.email()}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading}/>
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.trans('core.forum.change_email_submit_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    // If the user hasn't actually entered a different email address, we don't
    // need to do anything. Woot!
    if (this.email() === app.session.user.email()) {
      this.hide();
      return;
    }

    this.loading = true;

    app.session.user.save({email: this.email()}).then(
      () => {
        this.loading = false;
        this.success = true;
        m.redraw();
      },
      response => {
        this.loading = false;
        this.handleErrors(response);
      }
    );
  }
}
