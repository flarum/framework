import Modal from 'flarum/components/Modal';
import ForgotPasswordModal from 'flarum/components/ForgotPasswordModal';
import SignUpModal from 'flarum/components/SignUpModal';
import Alert from 'flarum/components/Alert';

/**
 * The `LogInModal` component displays a modal dialog with a login form.
 *
 * ### Props
 *
 * - `email`
 * - `password`
 */
export default class LogInModal extends Modal {
  constructor(...args) {
    super(...args);

    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    this.email = m.prop(this.props.email || '');

    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    this.password = m.prop(this.props.password || '');
  }

  className() {
    return 'LogInModal Modal--small';
  }

  title() {
    return 'Log In';
  }

  content() {
    return [
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input className="FormControl" name="email" placeholder="Username or Email"
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading} />
          </div>

          <div className="Form-group">
            <input className="FormControl" name="password" type="password" placeholder="Password"
              value={this.password()}
              onchange={m.withAttr('value', this.password)}
              disabled={this.loading} />
          </div>

          <div className="Form-group">
            <button className="Button Button--primary Button--block"
              type="submit"
              disabled={this.loading}>
              Log In
            </button>
          </div>
        </div>
      </div>,
      <div className="Modal-footer">
        <p className="LogInModal-forgotPassword">
          <a onclick={this.forgotPassword.bind(this)}>Forgot password?</a>
        </p>
        <p className="LogInModal-signUp">
          Don't have an account?{' '}
          <a onclick={this.signUp.bind(this)}>Sign Up</a>
        </p>
      </div>
    ];
  }

  /**
   * Open the forgot password modal, prefilling it with an email if the user has
   * entered one.
   */
  forgotPassword() {
    const email = this.email();
    const props = email.indexOf('@') !== -1 ? {email} : undefined;

    app.modal.show(new ForgotPasswordModal(props));
  }

  /**
   * Open the sign up modal, prefilling it with an email/username/password if
   * the user has entered one.
   */
  signUp() {
    const props = {password: this.password()};
    const email = this.email();
    props[email.indexOf('@') !== -1 ? 'email' : 'username'] = email;

    app.modal.show(new SignUpModal(props));
  }

  onready() {
    this.$('[name=' + (this.email() ? 'password' : 'email') + ']').select();
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const email = this.email();
    const password = this.password();

    app.session.login(email, password).then(
      () => {
        this.hide();
        if (this.props.onlogin) this.props.onlogin();
      },
      response => {
        this.loading = false;

        if (response && response.code === 'confirm_email') {
          this.alert = Alert.component({
            children: ['You need to confirm your email before you can log in. We\'ve sent a confirmation email to ', <strong>{response.email}</strong>, '. If it doesn\'t arrive soon, check your spam folder.']
          });
        } else {
          this.alert = Alert.component({
            type: 'error',
            children: 'Your login details were incorrect.'
          });
        }

        m.redraw();
        this.onready();
      }
    );
  }
}
