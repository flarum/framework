import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionLockedPost from './components/DiscussionLockedPost';

import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.PostTypes() //
    .add('discussionLocked', DiscussionLockedPost),

  new Extend.Model(Discussion) //
    .attribute<boolean>('isLocked')
    .attribute<boolean>('canLock'),
];
