import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';

export default class ConfirmPasswordModal extends Modal {
  init() {
    super.init();

    this.password = m.prop('');
  }

  className() {
    return 'ConfirmPasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.confirm_password.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input
              type="password"
              className="FormControl"
              bidi={this.password}
              placeholder={extractText(app.translator.trans('core.forum.confirm_password.password_placeholder'))}
              disabled={this.loading}/>
          </div>

          <div className="Form-group">
            <Button
              type="submit"
              className="Button Button--primary Button--block"
              loading={this.loading}>
              {app.translator.trans('core.forum.confirm_password.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.session.login(app.session.user.email(), this.password(), {errorHandler: this.onerror.bind(this)})
      .then(() => {
        this.success = true;
        this.hide();
        app.request(this.props.deferredRequest).then(response => this.props.deferred.resolve(response), response => this.props.deferred.reject(response));
      })
      .catch(this.loaded.bind(this));
  }

  onerror(error) {
    if (error.status === 401) {
      error.alert.props.children = app.translator.trans('core.forum.log_in.invalid_login_message');
    }

    super.onerror(error);
  }

  onhide() {
    if (this.success) return;

    this.props.deferred.reject(this.props.error);
  }
}
