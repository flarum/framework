import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';
import Discussion from 'flarum/common/models/Discussion';

import commonExtend from '../common/extend';
import NewPostNotification from './components/NewPostNotification';
import extendDeck from './extendDeck';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('following', '/following', IndexPage),

  new Extend.Notification() //
    .add('newPost', NewPostNotification),

  new Extend.Model(Discussion) //
    .attribute('subscription'),

  // flarum/deck is an optional dependency, so it has loaded before this runs.
  ...('flarum-deck' in flarum.extensions ? extendDeck() : []),
];
