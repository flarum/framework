import Extend from 'flarum/common/extenders';
import SuspendedGambit from './query/users/SuspendedGambit';
import User from 'flarum/common/models/User';

// prettier-ignore
export default [
  new Extend.Search()
    .gambit('users', SuspendedGambit),

  new Extend.Model(User)
    .attribute<boolean>('canSuspend'),
];
