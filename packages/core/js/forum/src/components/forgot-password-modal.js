import FormModal from 'flarum/components/form-modal';
import LoadingIndicator from 'flarum/components/loading-indicator';
import Alert from 'flarum/components/alert';
import icon from 'flarum/helpers/icon';

export default class ForgotPasswordModal extends FormModal {
  constructor(props) {
    super(props);

    this.email = m.prop(this.props.email || '');
    this.success = m.prop(false);
  }

  view() {
    if (this.success()) {
      var emailProviderName = this.email().split('@')[1];
    }

    return super.view({
      className: 'modal-sm forgot-password',
      title: 'Forgot Password',
      body: m('div.form-centered', this.success()
        ? [
          m('p.help-text', 'We\'ve sent you an email containing a link to reset your password. Check your spam folder if you don\'t receive it within the next minute or two.'),
          m('div.form-group', [
            m('a.btn.btn-primary.btn-block', {href: 'http://'+emailProviderName}, 'Go to '+emailProviderName)
          ])
        ]
        : [
          m('p.help-text', 'Enter your email address and we\'ll send you a link to reset your password.'),
          m('div.form-group', [
            m('input.form-control[name=email][placeholder=Email]', {value: this.email(), onchange: m.withAttr('value', this.email), disabled: this.loading()})
          ]),
          m('div.form-group', [
            m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading()}, 'Recover Password')
          ])
        ])
    });
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading(true);

    m.request({
      method: 'POST',
      url: app.config['api_url']+'/forgot',
      data: {email: this.email()},
      background: true,
      extract: xhr => {
        if (xhr.status === 404) {
          this.alert = new Alert({ type: 'warning', message: 'That email wasn\'t found in our database.' });
          throw new Error;
        }
        return null;
      }
    }).then(response => {
      this.loading(false);
      this.success(true);
      this.alert(null);
      m.redraw();
    }, response => {
      this.loading(false);
      this.handleErrors(response.errors);
    });
  }
}
