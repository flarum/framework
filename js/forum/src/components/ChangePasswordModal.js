import Modal from 'flarum/components/Modal';

/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal extends Modal {
  className() {
    return 'modal-sm change-password-modal';
  }

  title() {
    return 'Change Password';
  }

  content() {
    return (
      <div className="modal-body">
        <div className="form-centered">
          <p className="help-text">Click the button below and check your email for a link to change your password.</p>
          <div className="form-group">
            <button type="submit" className="btn btn-primary btn-block" disabled={this.loading}>Send Password Reset Email</button>
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
