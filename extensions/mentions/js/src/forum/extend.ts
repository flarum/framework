import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import MentionsUserPage from './components/MentionsUserPage';

export default [
  new Extend.Routes() //
    .add('user.mentions', '/u/:username/mentions', MentionsUserPage),

  new Extend.Model(Post) //
    .hasMany<Post>('mentionedBy')
    .attribute<number>('mentionedByCount'),

  new Extend.Model(User) //
    .attribute<boolean>('canMentionGroups'),
];
