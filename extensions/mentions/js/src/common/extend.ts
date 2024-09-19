import Extend from 'flarum/common/extenders';
import MentionedGambit from './query/posts/MentionedGambit';

export default [
  new Extend.Search() //
    .gambit('posts', MentionedGambit),
];
