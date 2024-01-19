import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import LikesUserPage from './components/LikesUserPage';

export default [
  new Extend.Routes() //
    .add('user.likes', '/u/:username/likes', LikesUserPage),

  new Extend.Model(Post) //
    .hasMany<User>('likes')
    .attribute<number>('likesCount')
    .attribute<boolean>('canLike'),
];
