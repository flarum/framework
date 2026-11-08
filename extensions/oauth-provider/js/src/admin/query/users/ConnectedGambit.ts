import app from 'flarum/admin/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';

/**
 * `connected:<client id>`: the members who have connected an application.
 */
export default class ConnectedGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('flarum-oauth-provider.lib.gambits.users.connected.key', {}, true);
  }

  canonicalKey(): string {
    return 'connected';
  }

  hint(): string {
    return app.translator.trans('flarum-oauth-provider.lib.gambits.users.connected.hint', {}, true);
  }

  filterKey(): string {
    return 'oauthClient';
  }
}
