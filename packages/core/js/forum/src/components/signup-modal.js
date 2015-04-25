import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';
import icon from 'flarum/helpers/icon';
import avatar from 'flarum/helpers/avatar';

export default class SignupModal extends Component {
  constructor(props) {
    super(props);

    this.username = m.prop();
    this.email = m.prop();
    this.password = m.prop();
    this.welcomeUser = m.prop();
    this.loading = m.prop(false);
  }

  view() {
    var welcomeUser = this.welcomeUser();
    var emailProviderName = welcomeUser && welcomeUser.email().split('@')[1];

    return m('div.modal-dialog.modal-sm.modal-signup', [
      m('div.modal-content', [
        m('button.btn.btn-icon.btn-link.close.back-control', {onclick: app.modal.close.bind(app.modal)}, icon('times')),
        m('form', {onsubmit: this.signup.bind(this)}, [
          m('div.modal-header', m('h3.title-control', 'Sign Up')),
          m('div.modal-body', [
            m('div.form-centered', [
              m('div.form-group', [
                m('input.form-control[name=username][placeholder=Username]', {onchange: m.withAttr('value', this.username)})
              ]),
              m('div.form-group', [
                m('input.form-control[name=email][placeholder=Email]', {onchange: m.withAttr('value', this.email)})
              ]),
              m('div.form-group', [
                m('input.form-control[type=password][name=password][placeholder=Password]', {onchange: m.withAttr('value', this.password)})
              ]),
              m('div.form-group', [
                m('button.btn.btn-primary.btn-block[type=submit]', 'Sign Up')
              ])
            ])
          ]),
          m('div.modal-footer', [
            m('p.log-in-link', ['Already have an account? ', m('a[href=javascript:;]', {onclick: app.login}, 'Log In')])
          ])
        ])
      ]),
      LoadingIndicator.component({className: 'modal-loading'+(this.loading() ? ' active' : '')}),
      welcomeUser ? m('div.signup-welcome', {style: 'background: '+this.welcomeUser().color(), config: this.fadeIn}, [
        avatar(welcomeUser),
        m('h3', 'Welcome, '+welcomeUser.username()+'!'),
        !welcomeUser.isConfirmed()
          ? [
            m('p', ['We\'ve sent a confirmation email to ', m('strong', welcomeUser.email()), '. If it doesn\'t arrive soon, check your spam folder.']),
            m('p', m('a.btn.btn-default', {href: 'http://'+emailProviderName}, 'Go to '+emailProviderName))
          ]
          : ''
      ]) : ''
    ])
  }

  fadeIn(element, isInitialized) {
    if (isInitialized) { return; }
    $(element).hide().fadeIn();
  }

  ready($modal) {
    $modal.find('[name=username]').focus();
  }

  signup(e) {
    e.preventDefault();
    this.loading(true);
    var self = this;

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
      m.redraw();
    });
  }
}
