import Modal from 'flarum/components/Modal';

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
    return 'Change Email';
  }

  content() {
    if (this.success) {
      const emailProviderName = this.email().split('@')[1];

      return (
        <div className="Modal-body">
          <div class="Form Form--centered">
            <p class="helpText">We've sent a confirmation email to <strong>{this.email()}</strong>. If it doesn't arrive soon, check your spam folder.</p>
            <div class="Form-group">
              <a href={'http://' + emailProviderName} className="Button Button--primary Button--block">Go to {emailProviderName}</a>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div class="Form Form--centered">
          <div class="Form-group">
            <input type="email" name="email" className="FormControl"
              placeholder={app.session.user.email()}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading}/>
          </div>
          <div class="Form-group">
            <button type="submit" className="Button Button--primary Button--block" disabled={this.loading}>Save Changes</button>
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
      () => {
        this.loading = false;
      }
    );
  }
}
