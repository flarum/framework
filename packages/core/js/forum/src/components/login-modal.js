import FormModal from 'flarum/components/form-modal';
import LoadingIndicator from 'flarum/components/loading-indicator';
import ForgotPasswordModal from 'flarum/components/forgot-password-modal';
import SignupModal from 'flarum/components/signup-modal';
import Alert from 'flarum/components/alert';
import icon from 'flarum/helpers/icon';

export default class LoginModal extends FormModal {
  constructor(props) {
    super(props);

    this.email = m.prop();
    this.password = m.prop();
  }

  view() {
    return super.view({
      className: 'modal-sm login-modal',
      title: 'Log In',
      body: [
        m('div.form-group', [
          m('input.form-control[name=email][placeholder=Username or Email]', {onchange: m.withAttr('value', this.email), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('input.form-control[type=password][name=password][placeholder=Password]', {onchange: m.withAttr('value', this.password), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading()}, 'Log In')
        ])
      ],
      footer: [
        m('p.forgot-password-link', m('a[href=javascript:;]', {onclick: () => app.modal.show(new ForgotPasswordModal({email: this.email()}))}, 'Forgot password?')),
        m('p.sign-up-link', [
          'Don\'t have an account? ',
          m('a[href=javascript:;]', {onclick: () => app.modal.show(new SignupModal())}, 'Sign Up')
        ])
      ]
    });
  }

  ready() {
    this.email() ? this.$('[name=password]').select() : this.$('[name=email]').select();
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading(true);
    app.session.login(this.email(), this.password()).then(() => {
      this.hide();
      this.props.callback && this.props.callback();
    }, response => {
      this.loading(false);
      this.alert = new Alert({ type: 'warning', message: 'Your login details were incorrect.' });
      m.redraw();
      this.ready();
    });
  }
}
