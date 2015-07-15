import Modal from 'flarum/components/Modal';
import LogInModal from 'flarum/components/LogInModal';
import avatar from 'flarum/helpers/avatar';

/**
 * The `SignUpModal` component displays a modal dialog with a singup form.
 *
 * ### Props
 *
 * - `username`
 * - `email`
 * - `password`
 */
export default class SignUpModal extends Modal {
  constructor(...args) {
    super(...args);

    /**
     * The value of the username input.
     *
     * @type {Function}
     */
    this.username = m.prop(this.props.username || '');

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

    /**
     * The user that has been signed up and that should be welcomed.
     *
     * @type {null|User}
     */
    this.welcomeUser = null;
  }

  className() {
    return 'modal-sm signup-modal' + (this.welcomeUser ? ' signup-modal-success' : '');
  }

  title() {
    return 'Sign Up';
  }

  body() {
    const body = [(
      <div className="form-centered">
        <div className="form-group">
          <input className="form-control" name="username" placeholder="Username"
            value={this.username()}
            onchange={m.withAttr('value', this.email)}
            disabled={this.loading} />
        </div>

        <div className="form-group">
          <input className="form-control" name="email" type="email" placeholder="Email"
            value={this.email()}
            onchange={m.withAttr('value', this.email)}
            disabled={this.loading} />
        </div>

        <div className="form-group">
          <input className="form-control" name="password" type="password" placeholder="Password"
            value={this.password()}
            onchange={m.withAttr('value', this.password)}
            disabled={this.loading} />
        </div>

        <div className="form-group">
          <button className="btn btn-primary btn-block"
            type="submit"
            disabled={this.loading}>
            Sign Up
          </button>
        </div>
      </div>
    )];

    if (this.welcomeUser) {
      const user = this.welcomeUser;
      const emailProviderName = user.email().split('@')[1];

      const fadeIn = (element, isInitialized) => {
        if (isInitialized) return;
        $(element).hide().fadeIn();
      };

      body.push(
        <div className="signup-welcome" style={{background: user.color()}} config={fadeIn}>
          <div className="darken-overlay"/>
          <div className="container">
            {avatar(user)}
            <h3>Welcome, {user.username()}!</h3>

            {user.isConfirmed() ? [
              <p>We've sent a confirmation email to <strong>{user.email()}</strong>. If it doesn't arrive soon, check your spam folder.</p>,
              <p><a href={`http://${emailProviderName}`} className="btn btn-primary">Go to {emailProviderName}</a></p>
            ] : (
              <p><button className="btn btn-primary" onclick={this.hide.bind(this)}>Dismiss</button></p>
            )}
          </div>
        </div>
      );
    }

    return body;
  }

  footer() {
    return [
      <p className="log-in-link">
        Already have an account?
        <a href="javascript:;" onclick={this.logIn.bind(this)}>Log In</a>
      </p>
    ];
  }

  /**
   * Open the log in modal, prefilling it with an email/username/password if
   * the user has entered one.
   */
  logIn() {
    const props = {
      email: this.email() || this.username(),
      password: this.password()
    };

    app.modal.show(new LogInModal(props));
  }

  onready() {
    if (this.props.username) {
      this.$('[name=email]').select();
    } else {
      super.onready();
    }
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const data = {
      username: this.username(),
      email: this.email(),
      password: this.password()
    };

    app.store.createRecord('users').save(data).then(
      user => {
        this.welcomeUser = user;
        this.loading = false;
        m.redraw();
      },
      response => {
        this.loading = false;
        this.handleErrors(response.errors);
      }
    );
  }
}
