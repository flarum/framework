import Extend from 'flarum/common/extenders';
import SubscriptionGambit from './query/discussions/SubscriptionGambit';

export default [
  new Extend.Search() //
    .gambit('discussions', SubscriptionGambit),
];
