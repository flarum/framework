import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

export default class Flag extends mixin(Model, {
  type: Model.attribute('type'),
  reason: Model.attribute('reason'),
  reasonDetail: Model.attribute('reasonDetail'),
  time: Model.attribute('time', Model.transformDate),

  post: Model.hasOne('post'),
  user: Model.hasOne('user')
}) {}
