import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Mithril from 'mithril';
import app from 'flarum/admin/app';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';

export interface IAuthMethodModalAttrs extends IInternalModalAttrs {
  onsubmit: (type: string, host: string, token: string) => void;
  type?: string;
  host?: string;
  token?: string;
}

export default class AuthMethodModal<CustomAttrs extends IAuthMethodModalAttrs = IAuthMethodModalAttrs> extends Modal<CustomAttrs> {
  protected type!: Stream<string>;
  protected host!: Stream<string>;
  protected token!: Stream<string>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.type = Stream(this.attrs.type || 'bearer');
    this.host = Stream(this.attrs.host || '');
    this.token = Stream(this.attrs.token || '');
  }

  className(): string {
    return 'AuthMethodModal Modal--small';
  }

  title(): Mithril.Children {
    const context = this.attrs.host ? 'edit' : 'add';
    return app.translator.trans(`flarum-extension-manager.admin.auth_config.${context}_label`);
  }

  content(): Mithril.Children {
    const types = {
      'github-oauth': app.translator.trans('flarum-extension-manager.admin.auth_config.types.github-oauth'),
      'gitlab-oauth': app.translator.trans('flarum-extension-manager.admin.auth_config.types.gitlab-oauth'),
      'gitlab-token': app.translator.trans('flarum-extension-manager.admin.auth_config.types.gitlab-token'),
      bearer: app.translator.trans('flarum-extension-manager.admin.auth_config.types.bearer'),
    };

    return (
      <div className="Modal-body">
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.type_label')}</label>
          <Select options={types} value={this.type()} onchange={this.type} />
        </div>
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.host_label')}</label>
          <input
            className="FormControl"
            bidi={this.host}
            placeholder={app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.host_placeholder')}
          />
        </div>
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.token_label')}</label>
          <textarea
            className="FormControl"
            oninput={(e: InputEvent) => this.token((e.target as HTMLTextAreaElement).value)}
            rows="6"
            placeholder={
              this.token().startsWith('unchanged:')
                ? extractText(app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.unchanged_token_placeholder'))
                : ''
            }
          >
            {this.token().startsWith('unchanged:') ? '' : this.token()}
          </textarea>
        </div>
        <div className="Form-group">
          <Button className="Button Button--primary" onclick={this.submit.bind(this)}>
            {app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.submit_button')}
          </Button>
        </div>
      </div>
    );
  }

  submit() {
    this.attrs.onsubmit(this.type(), this.host(), this.token());
    this.hide();
  }
}
