import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import LikesUserPage from './components/LikesUserPage';
import PostLikedNotification from './components/PostLikedNotification';

export default [
  new Extend.Routes() //
    .add('user.likes', '/u/:username/likes', LikesUserPage),

  new Extend.Notification() //
    .add('postLiked', PostLikedNotification),

  new Extend.Model(Post) //
    .hasMany<User>('likes')
    .attribute<number>('likesCount')
    .attribute<boolean>('canLike'),
];
