import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';
import Discussion from 'flarum/common/models/Discussion';

import commonExtend from '../common/extend';
import NewPostNotification from './components/NewPostNotification';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('following', '/following', IndexPage),

  new Extend.Notification() //
    .add('newPost', NewPostNotification),

  new Extend.Model(Discussion) //
    .attribute('subscription'),
];
