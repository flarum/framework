import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

export default class Report extends mixin(Model, {
  reporter: Model.attribute('reporter'),
  reason: Model.attribute('reason'),
  reasonDetail: Model.attribute('reasonDetail'),
  time: Model.attribute('time', Model.transformDate),

  post: Model.hasOne('post'),
  user: Model.hasOne('user')
}) {}
