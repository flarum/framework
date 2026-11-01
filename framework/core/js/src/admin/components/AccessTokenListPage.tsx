import app from '../../admin/app';

import IPAddress from '../../common/components/IPAddress';
import humanTime from '../../common/helpers/humanTime';
import type AccessToken from '../../common/models/AccessToken';
import type User from '../../common/models/User';
import ResourceListState from '../../common/resource/ResourceListState';
import type { SavedView } from '../../common/resource/ViewPreferences';
import ItemList from '../../common/utils/ItemList';
import ResourceListPage from './ResourceListPage';
import AccessTokenRevokeModal, { accessTokenTypeLabel } from './AccessTokenRevokeModal';
import AccessTokensDefinition from '../resource/definitions/AccessTokensDefinition';
import ResourceTable, { patchColumn, type ColumnData } from './resource/ResourceTable';

/**
 * The list's order when neither the URL nor the saved view names one: most recent activity first.
 */
export const DEFAULT_ACCESS_TOKEN_SORT = '-lastActivityAt';

/**
 * The list state: `DEFAULT_ACCESS_TOKEN_SORT` is the default view's sort.
 */
export class AccessTokenListState extends ResourceListState<AccessToken> {
  defaultView(): SavedView {
    return { ...super.defaultView(), sort: DEFAULT_ACCESS_TOKEN_SORT };
  }
}

/**
 * The access token table. The columns are inferred from the descriptor (an extension's `->ui()`
 * column included) and the core ones rendered for reading. A row's "Revoke" (see
 * `AccessTokensDefinition.resourceActions()`) opens `AccessTokenRevokeModal`.
 */
export class AccessTokenTable extends ResourceTable<AccessToken> {
  columns(): ItemList<ColumnData<AccessToken>> {
    const columns = super.columns();

    patchColumn(columns, 'user', {
      content: (token) => {
        const user = token.user();

        if (!user) return <span className="AccessTokenList-user">#{token.userId()}</span>;

        return (
          <a className="AccessTokenList-user" target="_blank" href={`${app.forum.attribute('baseUrl')}/u/${user.slug()}`}>
            {user.username()}
          </a>
        );
      },
      csv: (token) => (token.user() ? (token.user() as User).username() : String(token.userId() ?? '')),
      toggleable: false,
    });

    patchColumn(columns, 'type', {
      content: (token) => (
        <span className="AccessTokenList-type">
          {accessTokenTypeLabel(token.type())}
          {token.isCurrent() && <span className="AccessTokenList-current">{app.translator.trans('core.admin.access_tokens.current_session')}</span>}
        </span>
      ),
      csv: (token) => token.type(),
    });

    patchColumn(columns, 'lastIpAddress', {
      content: (token) => (token.lastIpAddress() ? <IPAddress key={token.id()} ip={token.lastIpAddress()} /> : null),
    });

    patchColumn(columns, 'createdAt', {
      content: (token) => (token.createdAt() ? humanTime(token.createdAt()) : null),
      csv: (token) => token.createdAt()?.toISOString() ?? null,
    });

    patchColumn(columns, 'lastActivityAt', {
      content: (token) =>
        token.lastActivityAt() ? (
          humanTime(token.lastActivityAt())
        ) : (
          <span className="AccessTokenList-never">{app.translator.trans('core.admin.access_tokens.never')}</span>
        ),
      csv: (token) => token.lastActivityAt()?.toISOString() ?? null,
    });

    return columns;
  }

  /** Revoking replaces deleting: `AccessTokenRevokeModal`, then a notice. */
  deleteModal(token: AccessToken) {
    return {
      modal: AccessTokenRevokeModal,
      attrs: {
        definition: this.attrs.definition,
        model: token,
        ondeleted: () => app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.access_tokens.revoked')),
      },
    };
  }
}

/**
 * Admin page listing every session, remembered session and developer token of the forum's users,
 * for actors with `moderateAccessTokens`.
 */
export default class AccessTokenListPage extends ResourceListPage<AccessTokensDefinition> {
  createDefinition() {
    return app.resources.get<AccessToken>('access-tokens') as AccessTokensDefinition;
  }

  createList() {
    return new AccessTokenListState(this.definition, { pageSize: 50 });
  }

  page() {
    return {
      className: 'AccessTokenListPage',
      icon: 'fas fa-user-lock',
      title: app.translator.trans('core.admin.access_tokens.title'),
      description: app.translator.trans('core.admin.access_tokens.description'),
    };
  }

  tableComponent() {
    return AccessTokenTable;
  }
}
