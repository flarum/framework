import app from '../../forum/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';

/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'ChangePasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_password.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields() {
    const fields = new ItemList<Mithril.Children>();

    fields.add('help', <p className="helpText">{app.translator.trans('core.forum.change_password.text')}</p>);

    fields.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('core.forum.change_password.send_button')}
        </Button>
      </div>
    );

    return fields;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/forgot',
        body: this.requestBody(),
      })
      .then(this.hide.bind(this), this.loaded.bind(this));
  }

  requestBody() {
    return { email: app.session.user!.email() };
  }
}
