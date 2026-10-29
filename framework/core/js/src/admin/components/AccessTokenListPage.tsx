import type Mithril from 'mithril';

import app from '../../admin/app';

import Button from '../../common/components/Button';
import Icon from '../../common/components/Icon';
import IPAddress from '../../common/components/IPAddress';
import Tooltip from '../../common/components/Tooltip';
import humanTime from '../../common/helpers/humanTime';
import type { IPageAttrs } from '../../common/components/Page';
import type AccessToken from '../../common/models/AccessToken';
import type User from '../../common/models/User';
import ResourceDefinition from '../../common/resource/ResourceDefinition';
import ResourceListState, { readQuery, writeQuery } from '../../common/resource/ResourceListState';
import type { Filter } from '../../common/resource/types';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';
import AdminPage from './AdminPage';
import AccessTokenRevokeModal, { accessTokenTypeLabel } from './AccessTokenRevokeModal';
import ResourceTable, { type ColumnData } from './resource/ResourceTable';

/**
 * The list's order when the URL names none: most recent activity first.
 */
export const DEFAULT_ACCESS_TOKEN_SORT = '-lastActivityAt';

/**
 * The access tokens resource: never created from the admin (developer tokens are created by their
 * owner on the forum), the type filter labelled, and the token's user included.
 */
export class AccessTokenDefinition extends ResourceDefinition<AccessToken> {
  constructor() {
    super('access-tokens');
  }

  query(): Record<string, unknown> {
    return { include: 'user' };
  }

  canCreate(): boolean {
    return false;
  }

  /**
   * Administrators only, like the user list: the export holds IP addresses.
   */
  canExport(): boolean {
    return !!app.session.user?.isAdmin() && super.canExport();
  }

  filters(): ItemList<Filter> {
    const filters = super.filters();

    if (filters.has('type')) {
      const filter = filters.get('type');
      const values = this.descriptor?.filters?.find((f) => f.key === 'type')?.enum ?? [];

      filter.input = { type: 'enum', choices: values.map((value) => [value, accessTokenTypeLabel(value)]) };
    }

    return filters;
  }
}

/**
 * The list state: without a `sort` in the URL, the most recently active tokens come first. The
 * default is written to the URL, so the sort controls show it.
 */
export class AccessTokenListState extends ResourceListState<AccessToken> {
  async load(): Promise<void> {
    if (this.syncUrl) {
      const query = readQuery();

      if (!query.has('sort')) {
        query.set('sort', DEFAULT_ACCESS_TOKEN_SORT);
        writeQuery(query);
      }
    }

    return super.load();
  }
}

/**
 * The access token table. The columns are inferred from the descriptor (an extension's `->ui()`
 * column included) and the core ones rendered for reading. A row's "Revoke" replaces the generic
 * delete; the actor's current session has none, since only logging out ends it.
 */
export class AccessTokenTable extends ResourceTable<AccessToken> {
  columns(): ItemList<ColumnData<AccessToken>> {
    const columns = super.columns();

    const patch = (key: string, column: Partial<ColumnData<AccessToken>>) => {
      if (columns.has(key)) columns.setContent(key, { ...columns.get(key), ...column });
    };

    patch('user', {
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

    patch('type', {
      content: (token) => (
        <span className="AccessTokenList-type">
          {accessTokenTypeLabel(token.type())}
          {token.isCurrent() && <span className="AccessTokenList-current">{app.translator.trans('core.admin.access_tokens.current_session')}</span>}
        </span>
      ),
      csv: (token) => token.type(),
    });

    patch('lastIpAddress', {
      content: (token) => (token.lastIpAddress() ? <IPAddress key={token.id()} ip={token.lastIpAddress()} /> : null),
    });

    patch('createdAt', {
      content: (token) => (token.createdAt() ? humanTime(token.createdAt()) : null),
      csv: (token) => token.createdAt()?.toISOString() ?? null,
    });

    patch('lastActivityAt', {
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

  rowActions(token: AccessToken): ItemList<Mithril.Children> {
    const items = super.rowActions(token);

    if (items.has('delete')) items.remove('delete');

    if (token.isCurrent()) {
      const label = extractText(app.translator.trans('core.admin.access_tokens.cannot_revoke_current'));

      items.add(
        'current',
        <Tooltip text={label}>
          <span className="AccessTokenList-currentLock" tabindex="0" aria-label={label}>
            <Icon name="fas fa-lock" />
          </span>
        </Tooltip>,
        90
      );
    } else if (token.canDelete()) {
      const label = extractText(app.translator.trans('core.admin.access_tokens.revoke_button'));

      items.add(
        'revoke',
        <Button
          className="Button Button--icon Button--danger AccessTokenList-revoke"
          icon="fas fa-ban fa-fw"
          title={label}
          aria-label={label}
          onclick={() => this.delete(token)}
        />,
        90
      );
    }

    return items;
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
export default class AccessTokenListPage extends AdminPage {
  protected definition!: AccessTokenDefinition;
  protected list!: AccessTokenListState;

  oninit(vnode: Mithril.Vnode<IPageAttrs, this>) {
    super.oninit(vnode);

    this.definition = new AccessTokenDefinition();
    this.list = new AccessTokenListState(this.definition, { pageSize: 50 });
  }

  headerInfo() {
    return {
      className: 'AccessTokenListPage',
      icon: 'fas fa-user-lock',
      title: app.translator.trans('core.admin.access_tokens.title'),
      description: app.translator.trans('core.admin.access_tokens.description'),
    };
  }

  content() {
    return <AccessTokenTable definition={this.definition} state={this.list} className="AccessTokenListPage-table" />;
  }
}
