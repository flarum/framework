import Extend from 'flarum/common/extenders';
import StickyGambit from './query/discussions/StickyGambit';

export default [
  new Extend.Search() //
    .gambit('discussions', StickyGambit),
];
