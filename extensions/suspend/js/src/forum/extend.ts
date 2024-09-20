import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

import commonExtend from '../common/extend';
import UserSuspendedNotification from './components/UserSuspendedNotification';
import UserUnsuspendedNotification from './components/UserUnsuspendedNotification';

export default [
  ...commonExtend,

  new Extend.Notification() //
    .add('userSuspended', UserSuspendedNotification)
    .add('userUnsuspended', UserUnsuspendedNotification),

  new Extend.Model(User)
    .attribute<Date | null | undefined, string | null | undefined>('suspendedUntil', Model.transformDate)
    .attribute<string | null | undefined>('suspendReason')
    .attribute<string | null | undefined>('suspendMessage'),
];
