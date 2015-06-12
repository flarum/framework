import FormModal from 'flarum/components/form-modal';
import LoadingIndicator from 'flarum/components/loading-indicator';
import LoginModal from 'flarum/components/login-modal';
import Alert from 'flarum/components/alert';
import icon from 'flarum/helpers/icon';
import avatar from 'flarum/helpers/avatar';

export default class SignupModal extends FormModal {
  constructor(props) {
    super(props);

    this.username = m.prop(this.props.username || '');
    this.email = m.prop(this.props.email || '');
    this.password = m.prop(this.props.password || '');
    this.welcomeUser = m.prop();
  }

  view() {
    var welcomeUser = this.welcomeUser();
    var emailProviderName = welcomeUser && welcomeUser.email().split('@')[1];

    var vdom = super.view({
      className: 'modal-sm signup-modal'+(welcomeUser ? ' signup-modal-success' : ''),
      title: 'Sign Up',
      body: m('div.form-centered', [
        m('div.form-group', [
          m('input.form-control[name=username][placeholder=Username]', {value: this.username(), onchange: m.withAttr('value', this.username), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('input.form-control[name=email][placeholder=Email]', {value: this.email(), onchange: m.withAttr('value', this.email), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('input.form-control[type=password][name=password][placeholder=Password]', {value: this.password(), onchange: m.withAttr('value', this.password), disabled: this.loading()})
        ]),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading()}, 'Sign Up')
        ])
      ]),
      footer: [
        m('p.log-in-link', [
          'Already have an account? ',
          m('a[href=javascript:;]', {onclick: () => app.modal.show(new LoginModal({email: this.email() || this.username(), password: this.password()}))}, 'Log In')
        ])
      ]
    });

    if (welcomeUser) {
      vdom.children.push(
        m('div.signup-welcome', {style: 'background: '+this.welcomeUser().color(), config: this.fadeIn}, [
          m('div.darken-overlay'),
          m('div.container', [
            avatar(welcomeUser),
            m('h3', 'Welcome, '+welcomeUser.username()+'!'),
            !welcomeUser.isConfirmed()
              ? [
                m('p', ['We\'ve sent a confirmation email to ', m('strong', welcomeUser.email()), '. If it doesn\'t arrive soon, check your spam folder.']),
                m('p', m('a.btn.btn-default', {href: 'http://'+emailProviderName}, 'Go to '+emailProviderName))
              ]
              : [
                m('p', m('a.btn.btn-default', {onclick: this.hide.bind(this)}, 'Dismiss'))
              ]
          ])
        ])
      )
    }

    return vdom;
  }

  ready() {
    if (this.props.username) {
      this.$('[name=email]').select();
    } else {
      super.ready();
    }
  }

  fadeIn(element, isInitialized) {
    if (isInitialized) { return; }
    $(element).hide().fadeIn();
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading(true);

    app.store.createRecord('users').save({
      username: this.username(),
      email: this.email(),
      password: this.password()
    }).then(user => {
      this.welcomeUser(user);
      this.loading(false);
      m.redraw();
    }, response => {
      this.loading(false);
      this.handleErrors(response.errors);
    });
  }
}
