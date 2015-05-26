import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';
import Alert from 'flarum/components/alert';
import icon from 'flarum/helpers/icon';

export default class ForgotPasswordModal extends Component {
  constructor(props) {
    super(props);

    this.email = m.prop();
    this.loading = m.prop(false);
    this.success = m.prop(false);
  }

  view() {
    return m('div.modal-dialog.modal-sm.modal-forgot-password', [
      m('div.modal-content', [
        m('button.btn.btn-icon.btn-link.close.back-control', {onclick: this.hide.bind(this)}, icon('times')),
        m('form', {onsubmit: this.onsubmit.bind(this)}, [
          m('div.modal-header', m('h3.title-control', 'Forgot Password')),
          this.props.message ? m('div.modal-alert.alert', this.props.message) : '',
          m('div.modal-body', [
            m('div.form-centered', this.success() ? 'Sent!' : [
              m('div.form-group', [
                m('input.form-control[name=email][placeholder=Email]', {onchange: m.withAttr('value', this.email)})
              ]),
              m('div.form-group', [
                m('button.btn.btn-primary.btn-block[type=submit]', 'Recover Password')
              ])
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

  hide() {
    app.modal.close();
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading(true);

    m.request({
      method: 'POST',
      url: app.config['api_url']+'/forgot',
      data: {email: this.email()},
      background: true
    }).then(response => {
      this.loading(false);
      this.success(true);
      m.redraw();
    }, response => {
      this.loading(false);
      m.redraw();
      app.alerts.dismiss(this.errorAlert);
      app.alerts.show(this.errorAlert = new Alert({ type: 'warning', message: 'Invalid credentials.' }));
    });
  }
}
