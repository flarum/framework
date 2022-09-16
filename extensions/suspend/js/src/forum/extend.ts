import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

export default [
  new Extend.Model(User)
    .attribute<boolean>('canSuspend')
    .attribute<Date, string>('suspendedUntil', Model.transformDate)
    .attribute<string>('suspendReason')
    .attribute<string>('suspendMessage'),
];
