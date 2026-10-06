import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import GlobalSearch from 'flarum/forum/components/GlobalSearch';
import GlobalMessagesSearchSource from './components/GlobalMessagesSearchSource';

/**
 * Private messages as a category of the global search, beside discussions,
 * posts and users. Guests have no conversations, so it isn't offered to them
 * at all.
 */
export default function addMessageSearch() {
  extend(GlobalSearch.prototype, 'sourceItems', function (items) {
    if (app.session.user) {
      items.add('messages', new GlobalMessagesSearchSource(), -10);
    }
  });
}
