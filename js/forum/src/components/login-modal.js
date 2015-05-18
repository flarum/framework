import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';
import SignupModal from 'flarum/components/signup-modal';
import icon from 'flarum/helpers/icon';

export default class LoginModal extends Component {
  constructor(props) {
    super(props);

    this.email = m.prop();
    this.password = m.prop();
    this.loading = m.prop(false);
  }

  view() {
    return m('div.modal-dialog.modal-sm.modal-login', [
      m('div.modal-content', [
        m('button.btn.btn-icon.btn-link.close.back-control', {onclick: app.modal.close.bind(app.modal)}, icon('times')),
        m('form', {onsubmit: this.login.bind(this)}, [
          m('div.modal-header', m('h3.title-control', 'Log In')),
          this.props.message ? m('div.modal-alert.alert', this.props.message) : '',
          m('div.modal-body', [
            m('div.form-centered', [
              m('div.form-group', [
                m('input.form-control[name=email][placeholder=Username or Email]', {onchange: m.withAttr('value', this.email)})
              ]),
              m('div.form-group', [
                m('input.form-control[type=password][name=password][placeholder=Password]', {onchange: m.withAttr('value', this.password)})
              ]),
              m('div.form-group', [
                m('button.btn.btn-primary.btn-block[type=submit]', 'Log In')
              ])
            ])
          ]),
          m('div.modal-footer', [
            m('p.forgot-password-link', m('a[href=javascript:;]', 'Forgot password?')),
            m('p.sign-up-link', [
              'Don\'t have an account? ',
              m('a[href=javascript:;]', {onclick: () => app.modal.show(new SignupModal())}, 'Sign Up')
            ])
          ])
        ])
      ]),
      LoadingIndicator.component({className: 'modal-loading'+(this.loading() ? ' active' : '')})
    ])
  }

  ready($modal) {
    $modal.find('[name=email]').focus();
  }

  login(e) {
    e.preventDefault();
    this.loading(true);
    app.session.login(this.email(), this.password()).then(() => {
      app.modal.close();
      this.props.callback && this.props.callback();
    }, (response) => {
      this.loading(false);
      m.redraw();
    });
  }
}
