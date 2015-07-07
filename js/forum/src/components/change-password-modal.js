import FormModal from 'flarum/components/form-modal';

export default class ChangePasswordModal extends FormModal {
  view() {
    return super.view({
      className: 'modal-sm change-password-modal',
      title: 'Change Password',
      body: m('div.form-centered', [
        m('p.help-text', 'Click the button below and check your email for a link to change your password.'),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading()}, 'Send Password Reset Email')
        ])
      ])
    });
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading(true);

    m.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl')+'/forgot',
      data: {email: app.session.user().email()},
      background: true
    }).then(response => {
      this.hide();
    });
  }
}
