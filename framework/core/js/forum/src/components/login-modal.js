import FormModal from 'flarum/components/form-modal';
import LoadingIndicator from 'flarum/components/loading-indicator';
import ForgotPasswordModal from 'flarum/components/forgot-password-modal';
import SignupModal from 'flarum/components/signup-modal';
import Alert from 'flarum/components/alert';
import ActionButton from 'flarum/components/action-button';
import icon from 'flarum/helpers/icon';

export default class LoginModal extends FormModal {
  constructor(props) {
    super(props);

    this.email = m.prop(this.props.email || '');
    this.password = m.prop(this.props.password || '');
  }

  view() {
    return super.view({
      className: 'modal-sm login-modal',
      title: 'Log In',
      body: [
        m('div.form-group', [
          m('input.form-control[name=email][placeholder=Username or Email]', {value: this.email(), onchange: m.withAttr('value', this.email), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('input.form-control[type=password][name=password][placeholder=Password]', {value: this.password(), onchange: m.withAttr('value', this.password), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading()}, 'Log In')
        ])
      ],
      footer: [
        m('p.forgot-password-link', m('a[href=javascript:;]', {onclick: () => {
          var email = this.email();
          var props = email.indexOf('@') !== -1 ? {email} : null;
          app.modal.show(new ForgotPasswordModal(props));
        }}, 'Forgot password?')),
        m('p.sign-up-link', [
          'Don\'t have an account? ',
          m('a[href=javascript:;]', {onclick: () => {
            var props = {password: this.password()};
            var email = this.email();
            props[email.indexOf('@') !== -1 ? 'email' : 'username'] = email;
            app.modal.show(new SignupModal(props));
          }}, 'Sign Up')
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
    var email = this.email();
    var password = this.password();

    app.session.login(email, password).then(() => {
      this.hide();
      this.props.callback && this.props.callback();
    }, response => {
      this.loading(false);
      if (response && response.code === 'confirm_email') {
        var state;

        this.alert(Alert.component({
          message: ['You need to confirm your email before you can log in. We\'ve sent a confirmation email to ', m('strong', response.email), '. If it doesn\'t arrive soon, check your spam folder.']
        }));
      } else {
        this.alert(Alert.component({
          type: 'warning',
          message: 'Your login details were incorrect.'
        }));
      }
      m.redraw();
      this.ready();
    });
  }
}
