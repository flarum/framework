import Modal from 'flarum/components/Modal';

/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal extends Modal {
  className() {
    return 'ChangePasswordModal Modal--small';
  }

  title() {
    return 'Change Password';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">Click the button below and check your email for a link to change your password.</p>
          <div className="Form-group">
            <button type="submit" className="Button Button--primary Button--block" disabled={this.loading}>Send Password Reset Email</button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/forgot',
      data: {email: app.session.user.email()}
    }).then(
      () => this.hide(),
      () => this.loading = false
    );
  }
}
