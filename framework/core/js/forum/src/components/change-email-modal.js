import FormModal from 'flarum/components/form-modal';
import Alert from 'flarum/components/alert';

export default class ChangeEmailModal extends FormModal {
  constructor(props) {
    super(props);

    this.success = m.prop(false);
    this.email = m.prop(app.session.user().email());
  }

  view() {
    if (this.success()) {
      var emailProviderName = this.email().split('@')[1];
    }
    var disabled = this.loading();

    return super.view({
      className: 'modal-sm change-email-modal',
      title: 'Change Email',
      body: this.success()
        ? [
          m('p.help-text', 'We\'ve sent a confirmation email to ', m('strong', this.email()), '. If it doesn\'t arrive soon, check your spam folder.'),
          m('div.form-group', [
            m('a.btn.btn-primary.btn-block', {href: 'http://'+emailProviderName}, 'Go to '+emailProviderName)
          ])
        ]
        : [
          m('div.form-group', [
            m('input.form-control[type=email][name=email]', {
              placeholder: app.session.user().email(),
              value: this.email(),
              onchange: m.withAttr('value', this.email),
              disabled
            })
          ]),
          m('div.form-group', [
            m('button.btn.btn-primary.btn-block[type=submit]', {disabled}, 'Save Changes')
          ])
        ]
    });
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.email() === app.session.user().email()) {
      this.hide();
      return;
    }

    this.loading(true);
    app.session.user().save({ email: this.email() }).then(() => {
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
