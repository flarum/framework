import app from '../../admin/app';

import type AccessToken from '../../common/models/AccessToken';
import ResourceListPage from './ResourceListPage';
import AccessTokensDefinition from '../resource/definitions/AccessTokensDefinition';

/**
 * The list's order when neither the URL nor the saved view names one: most recent activity first.
 */
export const DEFAULT_ACCESS_TOKEN_SORT = '-lastActivityAt';

/**
 * Admin page listing every session, remembered session and developer token of the forum's users,
 * for actors with `moderateAccessTokens`.
 */
export default class AccessTokenListPage extends ResourceListPage<AccessTokensDefinition> {
  createDefinition() {
    return app.resources.get<AccessToken>('access-tokens') as AccessTokensDefinition;
  }

  createList() {
    return import('./resource/tables').then(({ AccessTokenListState }) => new AccessTokenListState(this.definition, { pageSize: 50 }));
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
    return import('./resource/tables').then(({ AccessTokenTable }) => AccessTokenTable);
  }
}
