import type AccessToken from '../../common/models/AccessToken';
import ResourceListState from '../../common/resource/ResourceListState';
import type { SavedView } from '../../common/resource/ViewPreferences';
import { DEFAULT_ACCESS_TOKEN_SORT } from '../components/AccessTokenListPage';

/**
 * The access token list: `DEFAULT_ACCESS_TOKEN_SORT` is the default view's sort.
 */
export default class AccessTokenListState extends ResourceListState<AccessToken> {
  defaultView(): SavedView {
    return { ...super.defaultView(), sort: DEFAULT_ACCESS_TOKEN_SORT };
  }
}
