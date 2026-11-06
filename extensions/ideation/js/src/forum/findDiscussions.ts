import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type { ApiPayloadPlural, ApiQueryParamsPlural, ApiResponsePlural } from 'flarum/common/Store';

/**
 * `app.store.find('discussions', params)`, except that the answer reaches the
 * store only while `current()` still wants it. The store updates the models
 * already on screen as it takes an answer in, so an older answer that arrives
 * last would roll back a vote count, a voted state or a status the newer one
 * just showed. Resolves to null for an answer nobody wants any more.
 */
export default function findDiscussions(params: ApiQueryParamsPlural, current: () => boolean): Promise<ApiResponsePlural<Discussion> | null> {
  const filter = params.filter?.q ? app.search.gambits.apply('discussions', { ...params.filter }) : params.filter;

  return app
    .request<ApiPayloadPlural>({ method: 'GET', url: app.forum.attribute('apiUrl') + '/discussions', params: { ...params, filter } })
    .then((payload) => (current() ? app.store.pushPayload<Discussion[]>(payload) : null));
}
