import FormModal from 'flarum/components/form-modal';

export default class DeleteAccountModal extends FormModal {
  constructor(props) {
    super(props);

    this.confirmation = m.prop();
  }

  view() {
    return super.view({
      className: 'modal-sm change-password-modal',
      title: 'Delete Account',
      body: m('div.form-centered', [
        m('p.help-text', 'Hold up there skippy! If you delete your account, there\'s no going back. All of your posts will be kept, but no longer associated with your account.'),
        m('div.form-group', [
          m('input.form-control[name=confirm][placeholder=Type "DELETE" to proceed]', {oninput: m.withAttr('value', this.confirmation)})
        ]),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', {disabled: this.loading() || this.confirmation() != 'DELETE'}, 'Delete Account')
        ])
      ])
    });
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.confirmation() !== 'DELETE') return;

    this.loading(true);
    app.session.user().delete().then(() => app.session.logout());
  }
}
