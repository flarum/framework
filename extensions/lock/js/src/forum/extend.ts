import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionLockedPost from './components/DiscussionLockedPost';

export default [
  new Extend.PostTypes() //
    .add('discussionLocked', DiscussionLockedPost),

  new Extend.Model(Discussion) //
    .attribute<boolean>('isLocked')
    .attribute<boolean>('canLock'),
];
