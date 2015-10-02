import Modal from 'flarum/components/Modal';
import ForgotPasswordModal from 'flarum/components/ForgotPasswordModal';
import SignUpModal from 'flarum/components/SignUpModal';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import LogInButtons from 'flarum/components/LogInButtons';
import extractText from 'flarum/utils/extractText';

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
    return app.trans('core.forum.log_in_title');
  }

  content() {
    return [
      <div className="Modal-body">
        <LogInButtons/>

        <div className="Form Form--centered">
          <div className="Form-group">
            <input className="FormControl" name="email" placeholder={extractText(app.trans('core.forum.log_in_username_or_email_placeholder'))}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading} />
          </div>

          <div className="Form-group">
            <input className="FormControl" name="password" type="password" placeholder={extractText(app.trans('core.forum.log_in_password_placeholder'))}
              value={this.password()}
              onchange={m.withAttr('value', this.password)}
              disabled={this.loading} />
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.trans('core.forum.log_in_submit_button')
            })}
          </div>
        </div>
      </div>,
      <div className="Modal-footer">
        <p className="LogInModal-forgotPassword">
          <a onclick={this.forgotPassword.bind(this)}>{app.trans('core.forum.log_in_forgot_password_link')}</a>
        </p>

        {app.forum.attribute('allowSignUp') ? (
          <p className="LogInModal-signUp">
            {app.trans('core.forum.log_in_no_account_text')}
            <a onclick={this.signUp.bind(this)}>{app.trans('core.forum.log_in_sign_up_link')}</a>
          </p>
        ) : ''}
      </div>
    ];
  }

  /**
   * Open the forgot password modal, prefilling it with an email if the user has
   * entered one.
   *
   * @public
   */
  forgotPassword() {
    const email = this.email();
    const props = email.indexOf('@') !== -1 ? {email} : undefined;

    app.modal.show(new ForgotPasswordModal(props));
  }

  /**
   * Open the sign up modal, prefilling it with an email/username/password if
   * the user has entered one.
   *
   * @public
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
      null,
      response => {
        this.loading = false;

        if (response && response.code === 'confirm_email') {
          this.alert = Alert.component({
            children: app.trans('core.forum.log_in_confirmation_required_message', {email: response.email})
          });
        } else {
          this.alert = Alert.component({
            type: 'error',
            children: app.trans('core.forum.log_in_invalid_login_message')
          });
        }

        m.redraw();
        this.onready();
      }
    );
  }
}
