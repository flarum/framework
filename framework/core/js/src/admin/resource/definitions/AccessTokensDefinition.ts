import app from '../../app';
import type AccessToken from '../../../common/models/AccessToken';
import type { ResourceAction } from '../../../common/resource/actions';
import ResourceDefinition from '../../../common/resource/ResourceDefinition';
import { patchFilter } from '../../../common/resource/fieldsFromDescriptor';
import type { Filter } from '../../../common/resource/types';
import type ItemList from '../../../common/utils/ItemList';
import extractText from '../../../common/utils/extractText';
import { accessTokenTypeLabel } from '../../components/AccessTokenRevokeModal';

/**
 * The access tokens definition (`app.resources.get('access-tokens')`): never created from the admin (developer tokens are created by their
 * owner on the forum), the type filter labelled, and the token's user included.
 */
export default class AccessTokensDefinition extends ResourceDefinition<AccessToken> {
  constructor(type: string = 'access-tokens') {
    super(type);
  }

  query(): Record<string, unknown> {
    return { include: 'user' };
  }

  canCreate(): boolean {
    return false;
  }

  label(count: number): string {
    return extractText(app.translator.trans('core.admin.access_tokens.label', { count }));
  }

  /**
   * A developer token's title, else its type and device.
   */
  recordLabel(token: AccessToken): string {
    if (token.type() === 'developer') {
      return token.title() || extractText(app.translator.trans('core.admin.access_tokens.untitled'));
    }

    const type = accessTokenTypeLabel(token.type());

    return token.device() ? `${type} (${token.device()})` : type;
  }

  /**
   * Administrators only, like the user list: the export holds IP addresses.
   */
  canExport(): boolean {
    return !!app.session.user?.isAdmin() && super.canExport();
  }

  /**
   * "Revoke" replaces the built-in delete, on rows and on the selection. The actor's current
   * session has none, since only logging out ends it; a row opens the table's revoke modal.
   */
  resourceActions(): ItemList<ResourceAction<AccessToken>> {
    const items = super.resourceActions();

    if (items.has('delete')) items.remove('delete');

    items.add(
      'revoke',
      {
        key: 'revoke',
        label: app.translator.trans('core.admin.access_tokens.revoke_button'),
        icon: 'fas fa-ban',
        destructive: true,
        scope: 'both',
        confirm: (count) => app.translator.trans('core.admin.access_tokens.revoke.bulk_confirmation', { count }),
        applies: (token) => !!token.canDelete() && !token.isCurrent(),
        run: (token) => token.delete({}, { errorHandler: () => {} }),
        open: (token, context) => context.delete(token),
      },
      90
    );

    return items;
  }

  filters(): ItemList<Filter> {
    const filters = super.filters();
    const values = this.descriptor?.filters?.find((f) => f.key === 'type')?.enum ?? [];

    patchFilter(filters, 'type', { input: { type: 'enum', choices: values.map((value) => [value, accessTokenTypeLabel(value)]) } });

    return filters;
  }
}
