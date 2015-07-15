import Modal from 'flarum/components/Modal';
import Alert from 'flarum/components/Alert';

/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their email address and request a link to reset their password.
 *
 * ### Props
 *
 * - `email`
 */
export default class ForgotPasswordModal extends Modal {
  constructor(...args) {
    super(...args);

    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    this.email = m.prop(this.props.email || '');

    /**
     * Whether or not the password reset email was sent successfully.
     *
     * @type {Boolean}
     */
    this.success = false;
  }

  className() {
    return 'modal-sm forgot-password';
  }

  title() {
    return 'Forgot Password';
  }

  body() {
    if (this.success) {
      const emailProviderName = this.email().split('@')[1];

      return (
        <div className="form-centered">
          <p className="help-text">We've sent you an email containing a link to reset your password. Check your spam folder if you don't receive it within the next minute or two.</p>
          <div className="form-group">
            <a href={'http://' + emailProviderName} className="btn btn-primary btn-block">Go to {emailProviderName}</a>
          </div>
        </div>
      );
    }

    return (
      <div className="form-centered">
        <p className="help-text">Enter your email address and we will send you a link to reset your password.</p>
        <div className="form-group">
          <input className="form-control" name="email" type="email" placeholder="Email"
            value={this.email()}
            onchange={m.withAttr('value', this.email)}
            disabled={this.loading} />
        </div>
        <div className="form-group">
          <button type="submit" className="btn btn-primary btn-block" disabled={this.loading}>
            Recover Password
          </button>
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
      data: {email: this.email()},
      handlers: {
        404: () => {
          this.alert = new Alert({type: 'warning', message: 'That email wasn\'t found in our database.'});
          throw new Error();
        }
      }
    }).then(
      () => {
        this.loading = false;
        this.success = true;
        this.alert = null;
        m.redraw();
      },
      response => {
        this.loading = false;
        this.handleErrors(response.errors);
      }
    );
  }
}
