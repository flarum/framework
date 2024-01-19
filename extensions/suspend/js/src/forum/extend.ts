import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Model(User)
    .attribute<Date | null | undefined, string | null | undefined>('suspendedUntil', Model.transformDate)
    .attribute<string | null | undefined>('suspendReason')
    .attribute<string | null | undefined>('suspendMessage'),
];
