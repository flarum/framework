import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import LikesUserPage from './components/LikesUserPage';
import UserPageResolver from 'flarum/forum/resolvers/UserPageResolver';
import PostLikedNotification from './components/PostLikedNotification';

import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('user.likes', '/u/:username/likes', LikesUserPage, UserPageResolver),

  new Extend.Notification() //
    .add('postLiked', PostLikedNotification),

  new Extend.Model(Post) //
    .hasMany<User>('likes')
    .attribute<number>('likesCount')
    .attribute<boolean>('canLike'),
];
