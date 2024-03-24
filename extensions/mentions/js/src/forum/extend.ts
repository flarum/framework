import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import MentionsUserPage from './components/MentionsUserPage';
import PostMentionedNotification from './components/PostMentionedNotification';
import UserMentionedNotification from './components/UserMentionedNotification';
import GroupMentionedNotification from './components/GroupMentionedNotification';

export default [
  new Extend.Routes() //
    .add('user.mentions', '/u/:username/mentions', MentionsUserPage),

  new Extend.Model(Post) //
    .hasMany<Post>('mentionedBy')
    .attribute<number>('mentionedByCount'),

  new Extend.Notification() //
    .add('postMentioned', PostMentionedNotification)
    .add('userMentioned', UserMentionedNotification)
    .add('groupMentioned', GroupMentionedNotification),

  new Extend.Model(User) //
    .attribute<boolean>('canMentionGroups'),
];
