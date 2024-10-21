import Extend from 'flarum/common/extenders';
import LikedByGambit from './query/posts/LikedByGambit';

export default [
  new Extend.Search() //
    .gambit('posts', LikedByGambit),
];
