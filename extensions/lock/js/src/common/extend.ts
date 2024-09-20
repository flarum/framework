import Extend from 'flarum/common/extenders';
import LockedGambit from './query/discussions/LockedGambit';

export default [
  new Extend.Search() //
    .gambit('discussions', LockedGambit),
];
