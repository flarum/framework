import Modal from '../../common/components/Modal';
import ForgotPasswordModal from './ForgotPasswordModal';
import SignUpModal from './SignUpModal';
import Button from '../../common/components/Button';
import LogInButtons from './LogInButtons';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';

/**
 * The `LogInModal` component displays a modal dialog with a login form.
 *
 * ### Attrs
 *
 * - `identification`
 * - `password`
 */
export default class LogInModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The value of the identification input.
     *
     * @type {Function}
     */
    this.identification = Stream(this.attrs.identification || '');

    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    this.password = Stream(this.attrs.password || '');

    /**
     * The value of the remember me input.
     *
     * @type {Function}
     */
    this.remember = Stream(!!this.attrs.remember);
  }

  className() {
    return 'LogInModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.log_in.title');
  }

  content() {
    return [<div className="Modal-body">{this.body()}</div>, <div className="Modal-footer">{this.footer()}</div>];
  }

  body() {
    return [<LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
  }

  fields() {
    const items = new ItemList();

    items.add(
      'identification',
      <div className="Form-group">
        <input
          className="FormControl"
          name="identification"
          type="text"
          placeholder={extractText(app.translator.trans('core.forum.log_in.username_or_email_placeholder'))}
          bidi={this.identification}
          disabled={this.loading}
        />
      </div>,
      30
    );

    items.add(
      'password',
      <div className="Form-group">
        <input
          className="FormControl"
          name="password"
          type="password"
          placeholder={extractText(app.translator.trans('core.forum.log_in.password_placeholder'))}
          bidi={this.password}
          disabled={this.loading}
        />
      </div>,
      20
    );

    items.add(
      'remember',
      <div className="Form-group">
        <div>
          <label className="checkbox">
            <input type="checkbox" bidi={this.remember} disabled={this.loading} />
            {app.translator.trans('core.forum.log_in.remember_me_label')}
          </label>
        </div>
      </div>,
      10
    );

    items.add(
      'submit',
      <div className="Form-group">
        {Button.component(
          {
            className: 'Button Button--primary Button--block',
            type: 'submit',
            loading: this.loading,
          },
          app.translator.trans('core.forum.log_in.submit_button')
        )}
      </div>,
      -10
    );

    return items;
  }

  footer() {
    return [
      <p className="LogInModal-forgotPassword">
        <a onclick={this.forgotPassword.bind(this)}>{app.translator.trans('core.forum.log_in.forgot_password_link')}</a>
      </p>,

      app.forum.attribute('allowSignUp') ? (
        <p className="LogInModal-signUp">{app.translator.trans('core.forum.log_in.sign_up_text', { a: <a onclick={this.signUp.bind(this)} /> })}</p>
      ) : (
        ''
      ),
    ];
  }

  /**
   * Open the forgot password modal, prefilling it with an email if the user has
   * entered one.
   *
   * @public
   */
  forgotPassword() {
    const email = this.identification();
    const attrs = email.indexOf('@') !== -1 ? { email } : undefined;

    app.modal.show(ForgotPasswordModal, attrs);
  }

  /**
   * Open the sign up modal, prefilling it with an email/username/password if
   * the user has entered one.
   *
   * @public
   */
  signUp() {
    const attrs = { password: this.password() };
    const identification = this.identification();
    attrs[identification.indexOf('@') !== -1 ? 'email' : 'username'] = identification;

    app.modal.show(SignUpModal, attrs);
  }

  onready() {
    this.$('[name=' + (this.identification() ? 'password' : 'identification') + ']').select();
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const identification = this.identification();
    const password = this.password();
    const remember = this.remember();

    app.session
      .login({ identification, password, remember }, { errorHandler: this.onerror.bind(this) })
      .then(() => window.location.reload(), this.loaded.bind(this));
  }

  onerror(error) {
    if (error.status === 401) {
      error.alert.content = app.translator.trans('core.forum.log_in.invalid_login_message');
    }

    super.onerror(error);
  }
}
