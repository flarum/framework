import Extend from 'flarum/common/extenders';
import SuspendedGambit from './query/users/SuspendedGambit';

export default [
  new Extend.Search() //
    .gambit('users', SuspendedGambit),
];
