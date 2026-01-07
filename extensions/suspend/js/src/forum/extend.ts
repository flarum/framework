import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

export default [
  new Extend.Model(User)
    .attribute<boolean>('canSuspend')
    .attribute<Date | null, string | null>('suspendedUntil', Model.transformDate)
    .attribute<string | null | undefined>('suspendReason')
    .attribute<string | null | undefined>('suspendMessage'),
];
