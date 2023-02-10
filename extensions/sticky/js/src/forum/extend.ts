import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionStickiedPost from './components/DiscussionStickiedPost';

export default [
  new Extend.PostTypes() //
    .add('discussionStickied', DiscussionStickiedPost),

  new Extend.Model(Discussion) //
    .attribute<boolean>('isSticky')
    .attribute<boolean>('canSticky'),
];
