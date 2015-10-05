import Modal from 'flarum/components/Modal';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';

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
    return app.trans('core.forum.forgot_password_title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.trans('core.forum.forgot_password_email_sent_message')}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.trans('core.forum.forgot_password_dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.trans('core.forum.forgot_password_text')}</p>
          <div className="Form-group">
            <input className="FormControl" name="email" type="email" placeholder={extractText(app.trans('core.forum.forgot_password_email_placeholder'))}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.trans('core.forum.forgot_password_submit_button')
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
