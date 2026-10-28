import app from '../../admin/app';
import type Mithril from 'mithril';
import Button from '../../common/components/Button';
import type AccessToken from '../../common/models/AccessToken';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';
import ResourceDeleteModal, { type ResourceDeleteModalAttrs } from './resource/ResourceDeleteModal';

/**
 * The label of a token type: core's three types are translated, an extension's type is shown as
 * stored.
 */
export function accessTokenTypeLabel(type: string | null | undefined): string {
  switch (type) {
    case 'session':
      return extractText(app.translator.trans('core.admin.access_tokens.types.session'));
    case 'session_remember':
      return extractText(app.translator.trans('core.admin.access_tokens.types.session_remember'));
    case 'developer':
      return extractText(app.translator.trans('core.admin.access_tokens.types.developer'));
    default:
      return type ?? '';
  }
}

/**
 * A short name for one token: a developer token's title, else its type and device.
 */
export function accessTokenLabel(token: AccessToken): string {
  if (token.type() === 'developer') {
    return token.title() || extractText(app.translator.trans('core.admin.access_tokens.untitled'));
  }

  const type = accessTokenTypeLabel(token.type());

  return token.device() ? `${type} (${token.device()})` : type;
}

/**
 * Confirm and revoke one access token through `DELETE /api/access-tokens/{id}`.
 */
export default class AccessTokenRevokeModal<
  Attrs extends ResourceDeleteModalAttrs<AccessToken> = ResourceDeleteModalAttrs<AccessToken>,
> extends ResourceDeleteModal<AccessToken, Attrs> {
  className(): string {
    return `${super.className()} AccessTokenRevokeModal`;
  }

  title(): Mithril.Children {
    return app.translator.trans('core.admin.access_tokens.revoke.title');
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const token = this.attrs.model;
    const user = token.user();

    items.add(
      'confirmation',
      <p className="ResourceDeleteModal-confirmation">
        {app.translator.trans('core.admin.access_tokens.revoke.confirmation', {
          name: <strong>{accessTokenLabel(token)}</strong>,
          username: <strong>{user ? user.username() : `#${token.userId()}`}</strong>,
        })}
      </p>,
      100
    );

    return items;
  }

  footerItems(): ItemList<Mithril.Children> {
    const items = super.footerItems();

    items.setContent(
      'submit',
      <Button type="submit" className="Button Button--danger ResourceDeleteModal-submit AccessTokenRevokeModal-submit" loading={this.loading}>
        {app.translator.trans('core.admin.access_tokens.revoke.submit_button')}
      </Button>
    );

    return items;
  }
}
