import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

interface NewClientSecretModalAttrs extends IInternalModalAttrs {
  clientId: string;
  clientSecret: string;
  rotated?: boolean;
}

export default class NewClientSecretModal extends Modal<NewClientSecretModalAttrs> {
  className() {
    return 'NewClientSecretModal Modal--medium';
  }

  title() {
    return this.attrs.rotated
      ? app.translator.trans('flarum-oauth-provider.admin.secret_modal.rotated_title')
      : app.translator.trans('flarum-oauth-provider.admin.secret_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <p>
          {this.attrs.rotated
            ? app.translator.trans('flarum-oauth-provider.admin.secret_modal.rotated_intro')
            : app.translator.trans('flarum-oauth-provider.admin.secret_modal.intro')}
        </p>

        <div className="Form-group">
          <label>{app.translator.trans('flarum-oauth-provider.admin.secret_modal.client_id_label')}</label>
          <input type="text" className="FormControl" readOnly value={this.attrs.clientId} />
        </div>

        <div className="Form-group">
          <label>{app.translator.trans('flarum-oauth-provider.admin.secret_modal.client_secret_label')}</label>
          <input type="text" className="FormControl" readOnly value={this.attrs.clientSecret} />
        </div>

        <div className="Form-group">
          <Button className="Button Button--primary" onclick={() => app.modal.close()}>
            {app.translator.trans('flarum-oauth-provider.admin.secret_modal.close_button')}
          </Button>
        </div>
      </div>
    );
  }
}
