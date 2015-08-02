import Modal from 'flarum/components/Modal';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';

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
    return 'ForgotPasswordModal Modal--small';
  }

  title() {
    return app.trans('core.forgot_password');
  }

  content() {
    if (this.success) {
      const emailProviderName = this.email().split('@')[1];

      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.trans('core.password_reset_email_sent')}</p>
            <div className="Form-group">
              <a href={'http://' + emailProviderName} className="Button Button--primary Button--block">
                {app.trans('core.go_to', {location: emailProviderName})}
              </a>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.trans('core.forgot_password_help')}</p>
          <div className="Form-group">
            <input className="FormControl" name="email" type="email" placeholder={app.trans('core.email')}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.trans('core.recover_password')
            })}
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
        this.handleErrors(response);
      }
    );
  }
}
