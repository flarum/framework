import app from 'flarum/admin/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';

import OAuthClient from '../models/OAuthClient';

interface ClientFormModalAttrs extends IFormModalAttrs {
  client?: OAuthClient;
  onSaved: (client: OAuthClient, plainSecret: string | null) => void;
}

export default class ClientFormModal extends FormModal<ClientFormModalAttrs> {
  name!: Stream<string>;
  redirectUris!: Stream<string>;
  scopes!: Stream<string>;
  confidential!: Stream<boolean>;
  revoked!: Stream<boolean>;

  oninit(vnode: Mithril.Vnode<ClientFormModalAttrs, this>) {
    super.oninit(vnode);

    const client = this.attrs.client;

    this.name = Stream(client?.name() ?? '');
    this.redirectUris = Stream((client?.redirectUris() ?? []).join('\n'));
    this.scopes = Stream((client?.scopes() ?? ['openid', 'profile', 'email']).join(' '));
    this.confidential = Stream(client?.confidential() ?? true);
    this.revoked = Stream(client?.revoked() ?? false);
  }

  className() {
    return 'ClientFormModal Modal--medium';
  }

  title() {
    return this.attrs.client
      ? app.translator.trans('flarum-oauth-provider.admin.form.edit_title')
      : app.translator.trans('flarum-oauth-provider.admin.form.new_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('flarum-oauth-provider.admin.form.name_label')}</label>
            <input className="FormControl" bidi={this.name} required />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-oauth-provider.admin.form.redirect_uris_label')}</label>
            <textarea className="FormControl" bidi={this.redirectUris} rows={3} required />
            <p className="helpText">{app.translator.trans('flarum-oauth-provider.admin.form.redirect_uris_help')}</p>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-oauth-provider.admin.form.scopes_label')}</label>
            <input className="FormControl" bidi={this.scopes} />
            <p className="helpText">{app.translator.trans('flarum-oauth-provider.admin.form.scopes_help')}</p>
          </div>

          <div className="Form-group">
            <Switch state={this.confidential()} disabled={!!this.attrs.client} onchange={(checked: boolean) => this.confidential(checked)}>
              {app.translator.trans('flarum-oauth-provider.admin.form.confidential_label')}
            </Switch>
            <p className="helpText">{app.translator.trans('flarum-oauth-provider.admin.form.confidential_help')}</p>
          </div>

          {this.attrs.client ? (
            <div className="Form-group">
              <Switch state={this.revoked()} onchange={(checked: boolean) => this.revoked(checked)}>
                {app.translator.trans('flarum-oauth-provider.admin.form.revoked_label')}
              </Switch>
            </div>
          ) : null}

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={this.loading}>
              {app.translator.trans('flarum-oauth-provider.admin.form.save_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const redirectUris = this.redirectUris()
      .split('\n')
      .map((uri) => uri.trim())
      .filter((uri) => uri.length > 0);

    const scopes = this.scopes()
      .split(/\s+/)
      .map((s) => s.trim())
      .filter((s) => s.length > 0);

    const attrs = {
      name: this.name(),
      redirectUris,
      scopes,
      confidential: this.confidential(),
      revoked: this.revoked(),
    };

    this.loading = true;

    const existing = this.attrs.client;
    const record = existing ?? app.store.createRecord<OAuthClient>('oauth-provider-clients');

    record
      .save(attrs as unknown as Record<string, unknown>)
      .then((saved) => {
        const plainSecret = (saved as OAuthClient).plainSecret() ?? null;
        const onSaved = this.attrs.onSaved;
        this.hide();
        onSaved(saved as OAuthClient, plainSecret);
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
