import app from '../../admin/app';

import IPAddress from '../../common/components/IPAddress';
import humanTime from '../../common/helpers/humanTime';
import type AccessToken from '../../common/models/AccessToken';
import type User from '../../common/models/User';
import type ItemList from '../../common/utils/ItemList';
import AccessTokenRevokeModal, { accessTokenTypeLabel } from './AccessTokenRevokeModal';
import ResourceTable, { patchColumn, type ColumnData } from './resource/ResourceTable';

/**
 * The access token table. The columns are inferred from the descriptor (an extension's `->ui()`
 * column included) and the core ones rendered for reading. A row's "Revoke" (see
 * `AccessTokensDefinition.resourceActions()`) opens `AccessTokenRevokeModal`.
 */
export default class AccessTokenTable extends ResourceTable<AccessToken> {
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
