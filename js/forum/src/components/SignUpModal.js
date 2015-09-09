import Modal from 'flarum/components/Modal';
import LogInModal from 'flarum/components/LogInModal';
import avatar from 'flarum/helpers/avatar';
import Button from 'flarum/components/Button';

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
    return 'Modal--small SignUpModal' + (this.welcomeUser ? ' SignUpModal--success' : '');
  }

  title() {
    return app.trans('core.sign_up');
  }

  content() {
    return [
      <div className="Modal-body">
        {this.body()}
      </div>,
      <div className="Modal-footer">
        {this.footer()}
      </div>
    ];
  }

  body() {
    const body = [(
      <div className="Form Form--centered">
        <div className="Form-group">
          <input className="FormControl" name="username" placeholder={app.trans('core.username')}
            value={this.username()}
            onchange={m.withAttr('value', this.username)}
            disabled={this.loading} />
        </div>

        <div className="Form-group">
          <input className="FormControl" name="email" type="email" placeholder={app.trans('core.email')}
            value={this.email()}
            onchange={m.withAttr('value', this.email)}
            disabled={this.loading} />
        </div>

        <div className="Form-group">
          <input className="FormControl" name="password" type="password" placeholder={app.trans('core.password')}
            value={this.password()}
            onchange={m.withAttr('value', this.password)}
            disabled={this.loading} />
        </div>

        <div className="Form-group">
          {Button.component({
            className: 'Button Button--primary Button--block',
            type: 'submit',
            loading: this.loading,
            children: app.trans('core.sign_up')
          })}
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
        <div className="SignUpModal-welcome" style={{background: user.color()}} config={fadeIn}>
          <div className="darkenBackground">
            <div className="container">
              {avatar(user)}
              <h3>{app.trans('core.welcome_user', {user})}</h3>

              {!user.isActivated() ? [
                <p>{app.trans('core.confirmation_email_sent', {email: <strong>{user.email()}</strong>})}</p>,
                <p>
                  <a href={`http://${emailProviderName}`} className="Button Button--primary" target="_blank">
                    {app.trans('core.go_to', {location: emailProviderName})}
                  </a>
                </p>
              ] : (
                <p>
                  <button className="Button Button--primary" onclick={this.hide.bind(this)}>
                    {app.trans('core.dismiss')}
                  </button>
                </p>
              )}
            </div>
          </div>
        </div>
      );
    }

    return body;
  }

  footer() {
    return [
      <p className="SignUpModal-logIn">
        {app.trans('core.before_log_in_link')}{' '}
        <a onclick={this.logIn.bind(this)}>{app.trans('core.log_in')}</a>
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

    const data = this.submitData();

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

  submitData() {
    return {
      username: this.username(),
      email: this.email(),
      password: this.password()
    };
  }
}
