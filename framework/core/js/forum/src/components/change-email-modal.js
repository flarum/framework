import FormModal from 'flarum/components/form-modal';
import Alert from 'flarum/components/alert';

export default class ChangeEmailModal extends FormModal {
  constructor(props) {
    super(props);

    this.email = m.prop(app.session.user().email());
  }

  view() {
    return super.view({
      className: 'modal-sm change-email-modal',
      title: 'Change Email',
      body: [
        m('div.form-group', [
          m('input.form-control[type=email][name=email][placeholder=Email]', {value: this.email(), onchange: m.withAttr('value', this.email)})
        ]),
        m('div.form-group', [
          m('button.btn.btn-primary.btn-block[type=submit]', 'Save Changes')
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
      this.hide();
    }, response => {
      this.loading(false);
      this.alert = new Alert({ type: 'warning', message: response.errors.map((error, k) => [error.detail, k < response.errors.length - 1 ? m('br') : '']) });
      m.redraw();
      this.$('[name='+response.errors[0].path+']').select();
    });
  }
}
